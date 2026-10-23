<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * RoleSeeder
 *
 * Catálogo de roles y permisos de SEVEN, más la matriz rol->permiso que
 * usará la autorización granular cuando las rutas de proveedor/editor
 * estén listas.
 *
 * Idempotente: usa firstOrCreate + syncWithoutDetaching, así que se puede
 * ejecutar en cualquier entorno sin pisar asignaciones hechas a mano.
 *
 * Run with: php artisan db:seed --class=RoleSeeder
 */
class RoleSeeder extends Seeder
{
    /** name => [label, description] */
    private const ROLES = [
        Role::SUPER_ADMIN => [
            'Super administrador',
            'Control total de la plataforma SEVEN: usuarios, configuración y catálogo.',
        ],
        Role::ADMIN_SEVEN => [
            'Administrador SEVEN',
            'Opera el catálogo, las reservas y la moderación de contenidos.',
        ],
        Role::PROVEEDOR_TURISTICO => [
            'Proveedor turístico',
            'Gestiona el catálogo y las reservas de sus propios servicios (futuro).',
        ],
        Role::EMPLEADO_PROVEEDOR => [
            'Empleado de proveedor',
            'Acceso acotado a las reservas de su proveedor (futuro).',
        ],
        Role::EDITOR => [
            'Editor',
            'Edita noticias, banners y contenidos editoriales (futuro).',
        ],
    ];

    /** name => [label] */
    private const PERMISSIONS = [
        'admin.access' => 'Acceder al panel de administración',
        'catalog.manage' => 'Gestionar categorías, banners, promociones y media',
        'content.manage' => 'Gestionar tours, alojamiento, restaurantes y noticias',
        'providers.manage' => 'Gestionar proveedores turísticos',
        'bookings.manage' => 'Consultar y actualizar el estado de reservas',
        'comments.moderate' => 'Moderar comentarios de usuarios',
        'users.manage' => 'Gestionar usuarios administrativos',
    ];

    /** Rol => permisos (vacío = aún sin asignación) */
    private const MATRIX = [
        Role::SUPER_ADMIN => ['*'],
        Role::ADMIN_SEVEN => [
            'admin.access',
            'catalog.manage',
            'content.manage',
            'providers.manage',
            'bookings.manage',
            'comments.moderate',
        ],
        Role::PROVEEDOR_TURISTICO => ['catalog.manage', 'bookings.manage'],
        Role::EMPLEADO_PROVEEDOR => ['bookings.manage'],
        Role::EDITOR => ['content.manage', 'comments.moderate'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $label) {
            Permission::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        foreach (self::ROLES as $name => [$label, $description]) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['label' => $label, 'description' => $description],
            );

            $permissions = self::MATRIX[$name] ?? [];

            if ($permissions === ['*']) {
                $permissions = array_keys(self::PERMISSIONS);
            }

            // syncWithoutDetaching: re-sear no borra grants hechos a mano.
            $role->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('name', $permissions)->pluck('id')
            );
        }

        $this->backfillFromLegacyRoleColumn();
    }

    /**
     * Migra la columna string `users.role` (admin | provider | customer) a
     * la pivote `role_user`, solo para usuarios que todavía no tienen rol.
     *
     * Es lo que permite que una base ya sembrada con el sistema anterior
     * pueda iniciar sesión en el panel sin rehacer el seed completo. Los
     * usuarios nuevos (MarketplaceSeeder) se asignan al crearse.
     */
    private function backfillFromLegacyRoleColumn(): void
    {
        $map = [
            'admin' => Role::SUPER_ADMIN,
            'provider' => Role::PROVEEDOR_TURISTICO,
        ];

        foreach ($map as $legacyRole => $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            User::query()
                ->where('role', $legacyRole)
                ->whereDoesntHave('roles')
                ->get()
                ->each(fn (User $user) => $user->roles()->attach($role));
        }
    }
}
