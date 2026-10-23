<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Política de sesiones ante cambio de contraseña: si la contraseña
     * cambia, todas las sesiones (tokens personales) quedan revocadas. Un
     * cambio de contraseña suele deberse a un compromiso, así que no tiene
     * sentido conservar tokens emitidos con la credencial anterior.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if ($user->wasChanged('password')) {
                $user->tokens()->delete();
            }
        });
    }

    /*
    |----------------------------------------------------------------------
    | Roles y permisos (autorización)
    |----------------------------------------------------------------------
    | La columna string `role` se mantiene por compatibilidad con datos
    | existentes, pero la autorización real vive en la pivote `role_user`.
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Proveedor cuyo dueño es esta cuenta (panel /provider/*).
     *
     * Relación 1:1 por providers.user_id (única desde la migración de
     * tenancy); los admins globales no tienen proveedor.
     */
    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    /**
     * Permisos heredados de los roles del usuario.
     *
     * No es una relación Eloquent directa: no existe pivote user<->permission.
     * Se resuelve sobre `roles` ya cargados para no disparar queries extra
     * por cada llamada.
     *
     * @return EloquentCollection<int, Permission>
     */
    public function permissions(): EloquentCollection
    {
        $this->loadMissing('roles.permissions');

        return EloquentCollection::make(
            $this->roles
                ->pluck('permissions')
                ->flatten()
                ->unique('id')
                ->values()
                ->all()
        );
    }

    /** @param  array<int, string>  $roles */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains(
            fn (Role $candidate) => strcasecmp($candidate->name, $role) === 0
        );
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->contains('name', $permission);
    }

    /** Asigna roles por nombre sin quitar los que ya tenía (idempotente). */
    public function assignRole(string ...$roles): void
    {
        $ids = Role::query()->whereIn('name', $roles)->pluck('id');

        $this->roles()->syncWithoutDetaching($ids);
    }

    /** @return list<string> */
    public function rolesNames(): array
    {
        return $this->roles->pluck('name')->values()->all();
    }
}
