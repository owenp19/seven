<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Provider;
use App\Repositories\BaseRepository;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * AdminCrudController
 *
 * CRUD genérico para el backoffice. Cada entidad del catálogo aporta su
 * repository, resource, reglas de validación y label; la mecánica de
 * listar/persistir/responder queda centralizada aquí.
 *
 * Dos superficies comparten esta clase (ver routes/api/v1/api.php):
 *  - /api/v1/admin/* con role:SUPER_ADMIN,ADMIN_SEVEN: sin tenant, ve todo.
 *  - /api/v1/provider/* con role:PROVEEDOR_TURISTICO + provider.scope:
 *    el middleware inyecta SU proveedor y aquí se filtra cada query por
 *    provider_id, se inyecta el alta/edición y un recurso ajeno responde
 *    404 (defensa extra: policies en authorizeModel).
 */
abstract class AdminCrudController extends BaseController
{
    abstract protected function repository(): BaseRepository;

    abstract protected function resourceClass(): string;

    abstract protected function rules(bool $updating, ?int $id = null): array;

    abstract protected function label(): string;

    /**
     * Alta sin provider_id desde el backoffice global: se asigna el
     * proveedor de plataforma (columna NOT NULL). Solo lo activan las
     * verticales clásicas cuya regla sigue siendo `nullable`.
     */
    protected bool $platformFallback = false;

    /**
     * Campos que SOLO el backoffice global puede fijar (aprobación,
     * verificación, ratings, vinculación de cuentas…). En el panel del
     * proveedor (tenant activo) se descartan del payload aunque vengan en
     * el request: sin esto un PROVEEDOR_TURISTICO podía aprobarse a sí
     * mismo, marcarse como verificado o manipular su rating.
     *
     * @return array<int, string>
     */
    protected function adminOnlyFields(): array
    {
        return [];
    }

    /**
     * Elimina los campos privilegiados cuando la petición llega desde el
     * panel del proveedor. El backoffice global (sin tenant) los conserva.
     */
    private function stripAdminOnlyFields(array $data): array
    {
        if (tenantProvider() === null) {
            return $data;
        }

        foreach ($this->adminOnlyFields() as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Relations eager-load in index/show.
     */
    protected function with(): array
    {
        return [];
    }

    /**
     * Carga las relaciones declaradas en `with()` sobre un registro ya escrito.
     *
     * `store`/`show`/`update` devuelven el modelo recién guardado, que viene
     * sin relaciones: los resources las exponen con `whenLoaded`, así que sin
     * esto la respuesta del panel salía con los objetos anidados en `null`
     * aunque la relación exista en la base. El operador de una promoción o de
     * una noticia era el caso visible.
     */
    private function withRelations(Model $model): Model
    {
        $relations = $this->with();

        return $relations === [] ? $model : $model->load($relations);
    }

    /**
     * Columnas que el filtro `?q=` del panel recorre con LIKE.
     *
     * El backoffice busca siempre por subcadena ("casa" debe encontrar
     * "casa de la playa"), así que aquí no se usa el modo fulltext de la API
     * pública: MATCH...AGAINST no hace coincidencia parcial y devolvería
     * menos resultados de los que el operador espera.
     */
    protected function searchableColumns(): array
    {
        return [];
    }

    /**
     * ¿El modelo expone provider_id? (los recursos platform-only, como
     * categories o banners, no lo tienen y quedan fuera del scoping).
     */
    protected function hasProviderColumn(): bool
    {
        return in_array(
            'provider_id',
            $this->repository()->query()->getModel()->getFillable(),
            true,
        );
    }

    /**
     * Aplica el tenant activo (middleware provider.scope) a un query.
     * Sin tenant —backoffice global y API pública— el query queda igual.
     */
    protected function scopeQuery(Builder $query): Builder
    {
        $provider = tenantProvider();

        if ($provider !== null && $this->hasProviderColumn()) {
            $query->where($query->getModel()->getTable().'.provider_id', $provider->id);
        }

        return $query;
    }

    /**
     * Busca por id dentro del tenant: un recurso ajeno responde 404 y no
     * revela que existe.
     */
    protected function findScoped(int $id): Model
    {
        return $this->scopeQuery($this->repository()->query())->findOrFail($id);
    }

    /**
     * Servicio de auditoría (usuario + acción + recurso).
     */
    protected function audit(): AuditLogger
    {
        return app(AuditLogger::class);
    }

    /**
     * Campos cuyo valor sí interesa auditar (estados/aprobaciones) sin
     * volcar datos personales del payload completo.
     *
     * @var list<string>
     */
    protected array $auditableStateFields = [
        'status',
        'verification_status',
        'is_active',
        'is_verified',
        'published',
    ];

    /**
     * Metadata de auditoría: nombres de los campos tocados y valores de los
     * campos de estado. Nunca el payload crudo (podría llevar PII).
     */
    protected function auditMetadata(array $data): array
    {
        return [
            'attributes' => array_keys($data),
            'state' => array_intersect_key($data, array_flip($this->auditableStateFields)),
        ];
    }

    private function auditAction(string $verb, Model $model): string
    {
        return Str::snake(class_basename($model)).'.'.$verb;
    }

    /**
     * Defensa en profundidad además del scoping: las policies confirman la
     * propiedad. El token compartido legacy no tiene usuario asociado y
     * conserva el comportamiento global de la transición.
     */
    protected function authorizeModel(string $ability, Model $model): void
    {
        $user = request()->user('sanctum') ?? request()->user();

        if ($user === null) {
            return;
        }

        Gate::forUser($user)->authorize($ability, $model);
    }

    /**
     * Filtros de listar por defecto: `?status=` y `?q=` dentro del tenant.
     *
     * Los controllers que necesitan un filtro adicional sobrescriben este
     * método y llaman a `parent::filters()` para no perder los comunes.
     */
    protected function filters(Request $request, Builder $query): Builder
    {
        $query = $this->scopeQuery($query);

        $status = $request->input('status');

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $columns = $this->searchableColumns();
        $term = $request->input('q');

        if ($columns !== [] && is_string($term) && trim($term) !== '') {
            $term = trim($term);

            $query->where(function (Builder $inner) use ($term, $columns) {
                foreach ($columns as $column) {
                    $inner->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->filters($request, $this->repository()->query()->with($this->with()));
        $result = $query->latest()->paginate(apiPerPage($request));

        $class = $this->resourceClass();

        return $this->success(
            data: $class::collection($result),
            message: $this->label().' list retrieved successfully.',
            meta: [
                'pagination' => [
                    'total' => $result->total(),
                    'per_page' => $result->perPage(),
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                ],
            ],
        );
    }

    public function store(Request $request)
    {
        $provider = tenantProvider();
        $rules = $this->rules(false);

        // El panel del proveedor no elige provider_id: se ignora lo que
        // envíe y se fuerza el suyo (y la regla `required` del catálogo
        // marketplace no aplica a su propia alta).
        if ($provider !== null) {
            unset($rules['provider_id']);
        }

        $data = $this->stripAdminOnlyFields($request->validate($rules));

        if ($this->hasProviderColumn()) {
            if ($provider !== null) {
                $data['provider_id'] = $provider->id;
            } elseif (($data['provider_id'] ?? null) === null && $this->platformFallback) {
                $data['provider_id'] = Provider::platform()->id;
            }
        }

        $model = $this->repository()->create($data);

        $this->audit()->recordFor($request, $this->auditAction('created', $model), $model, $this->auditMetadata($data));

        $class = $this->resourceClass();

        return $this->success(
            data: $class::make($this->withRelations($model)),
            message: $this->label().' created successfully.',
            status: 201,
        );
    }

    public function show(int $id)
    {
        $model = $this->findScoped($id);
        $this->authorizeModel('view', $model);

        $class = $this->resourceClass();

        return $this->success(
            data: $class::make($this->withRelations($model)),
            message: $this->label().' retrieved successfully.',
        );
    }

    public function update(int $id, Request $request)
    {
        $model = $this->findScoped($id);
        $this->authorizeModel('update', $model);

        // PUT/PATCH en el panel aceptan actualización parcial: lo que la
        // entidad marca como "required" para el alta pasa a "sometimes".
        $rules = array_map(
            fn (array $rule) => array_map(
                fn (mixed $r) => $r === 'required' ? 'sometimes' : $r,
                $rule
            ),
            $this->rules(true, $id)
        );

        $provider = tenantProvider();

        // Un servicio no se puede mover de proveedor desde el panel del
        // dueño: el valor del request se descarta y manda el tenant.
        if ($provider !== null) {
            unset($rules['provider_id']);
        }

        $data = $this->stripAdminOnlyFields($request->validate($rules));

        if ($this->hasProviderColumn()) {
            if ($provider !== null) {
                $data['provider_id'] = $provider->id;
            } elseif (array_key_exists('provider_id', $data) && $data['provider_id'] === null && $this->platformFallback) {
                $data['provider_id'] = Provider::platform()->id;
            }
        }

        $previousState = $this->auditMetadata($model->only($this->auditableStateFields))['state'];

        $model = $this->repository()->update($id, $data);

        $metadata = $this->auditMetadata($data);
        $metadata['previous'] = $previousState;

        $this->audit()->recordFor($request, $this->auditAction('updated', $model), $model, $metadata);

        $class = $this->resourceClass();

        return $this->success(
            data: $class::make($this->withRelations($model)),
            message: $this->label().' updated successfully.',
        );
    }

    /**
     * Relations whose rows would be orphaned by a hard delete.
     *
     * `bookings` is polymorphic, so it is resolved against the concrete
     * model class. Overridable per entity.
     */
    protected function deleteBlockers(int $id): array
    {
        $model = $this->repository()->findOrFail($id);
        $class = $model::class;

        return Booking::query()
            ->where('bookable_type', $class)
            ->where('bookable_id', $id)
            ->exists()
            ? ['bookings' => ['Este servicio tiene reservas asociadas y no se puede eliminar.']]
            : [];
    }

    public function destroy(int $id, Request $request)
    {
        $model = $this->findScoped($id);
        $this->authorizeModel('delete', $model);

        // `?force=1` is an explicit override for the operator: it is the only
        // way to remove a record that still has history pointing at it. The
        // provider panel never bypasses the blockers, even if it asks to force.
        $allowForce = tenantProvider() === null && $request->boolean('force');

        if (! $allowForce) {
            $blockers = $this->deleteBlockers($id);

            if ($blockers !== []) {
                throw ValidationException::withMessages($blockers);
            }
        }

        $action = $this->auditAction('deleted', $model);

        $this->repository()->delete($id);

        $this->audit()->recordFor($request, $action, $model, ['forced' => $allowForce]);

        return $this->success(
            message: $this->label().' deleted successfully.',
        );
    }
}
