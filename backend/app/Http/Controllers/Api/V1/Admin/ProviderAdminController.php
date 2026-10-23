<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\ProviderResource;
use App\Models\Provider;
use App\Repositories\BaseRepository;
use App\Repositories\ProviderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProviderAdminController extends AdminCrudController
{
    public function __construct(private readonly ProviderRepository $providers) {}

    protected function repository(): BaseRepository
    {
        return $this->providers;
    }

    protected function resourceClass(): string
    {
        return ProviderResource::class;
    }

    protected function label(): string
    {
        return 'Provider';
    }

    protected function with(): array
    {
        return [];
    }

    protected function searchableColumns(): array
    {
        return ['business_name', 'owner_name'];
    }

    /**
     * En el panel del proveedor la "columna de tenancy" es la propia fila:
     * el dueño solo ve y edita su registro (listado limitado a una fila y
     * un id ajeno responde 404). Sin tenant mantiene el scoping global.
     */
    protected function scopeQuery(Builder $query): Builder
    {
        $provider = tenantProvider();

        if ($provider !== null) {
            return $query->where($query->getModel()->getTable().'.id', $provider->id);
        }

        return parent::scopeQuery($query);
    }

    /**
     * No se borra un proveedor con servicios: el cascade de las verticales
     * marketplace dejaría reservas apuntando a un bookable inexistente y el
     * SET NULL de las clásicas chocaría con provider_id NOT NULL. Tampoco
     * con ?force=1; primero hay que reasignar o eliminar sus servicios.
     */
    protected function deleteBlockers(int $id): array
    {
        $provider = $this->repository()->findOrFail($id);

        foreach (Provider::serviceModels() as $class) {
            $relation = Str::of(class_basename($class))->plural()->snake();

            if ($provider->{$relation}()->exists()) {
                return ['provider' => ['Este proveedor tiene servicios asociados y no se puede eliminar.']];
            }
        }

        return [];
    }

    public function destroy(int $id, Request $request)
    {
        $model = $this->findScoped($id);
        $this->authorizeModel('delete', $model);

        $blockers = $this->deleteBlockers($id);

        if ($blockers !== []) {
            throw ValidationException::withMessages($blockers);
        }

        $this->repository()->delete($id);

        $this->audit()->recordFor($request, 'provider.deleted', $model, []);

        return $this->success(
            message: $this->label().' deleted successfully.',
        );
    }

    protected function filters(Request $request, Builder $query): Builder
    {
        $type = $request->input('type');

        if (is_string($type) && $type !== '') {
            $query->where('type', $type);
        }

        return parent::filters($request, $query);
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('providers', 'user_id')->whereNotNull('user_id')->ignore($id)],
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['tour_operator', 'restaurant', 'native_inn', 'experience_provider', 'bakery'])],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
            'verification_status' => ['sometimes', Rule::in(['unverified', 'verified', 'suspended'])],
        ];
    }

    /**
     * El dueño no puede aprobarse, verificarse ni reasignar la cuenta
     * propietaria de su propio registro; eso es potestad del backoffice.
     */
    protected function adminOnlyFields(): array
    {
        return ['user_id', 'status', 'verification_status'];
    }
}
