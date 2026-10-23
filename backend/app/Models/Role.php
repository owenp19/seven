<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Rol de autorización de SEVEN.
 *
 * El nombre es el identificador canónico que viaja en los parámetros del
 * middleware (`role:SUPER_ADMIN,ADMIN_SEVEN`). Se definen aquí tanto los
 * roles activos del backoffice como los futuros (proveedor turístico,
 * empleado de proveedor, editor) para que el esquema y los middlewares no
 * cambien cuando se habiliten.
 */
class Role extends Model
{
    /*
    |------------------------------------------------------------------
    | Roles activos (backoffice)
    |------------------------------------------------------------------
    */

    /** Acceso total: usuarios, configuración y todo el catálogo. */
    public const SUPER_ADMIN = 'SUPER_ADMIN';

    /** Administrador SEVEN: opera el catálogo y las reservas. */
    public const ADMIN_SEVEN = 'ADMIN_SEVEN';

    /*
    |------------------------------------------------------------------
    | Roles futuros (reservados, sin rutas todavía)
    |------------------------------------------------------------------
    */

    /** Operador turístico sobre su propio catálogo. */
    public const PROVEEDOR_TURISTICO = 'PROVEEDOR_TURISTICO';

    /** Empleado de un proveedor con acceso acotado. */
    public const EMPLEADO_PROVEEDOR = 'EMPLEADO_PROVEEDOR';

    /** Edición de contenidos (noticias, banner) sin acceso a reservas. */
    public const EDITOR = 'EDITOR';

    /** Roles que pueden iniciar sesión en /api/v1/admin/auth/login. */
    public const ADMIN = [self::SUPER_ADMIN, self::ADMIN_SEVEN];

    /** @var list<string> */
    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
