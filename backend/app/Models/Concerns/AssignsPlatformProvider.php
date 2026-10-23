<?php

namespace App\Models\Concerns;

use App\Models\Provider;

/**
 * Servicio sin proveedor explícito = gestión directa de SEVEN.
 *
 * `provider_id` es NOT NULL en las siete verticales de servicio, así que
 * cualquier alta que llegue sin proveedor (fixtures, seeders o llamadas
 * directas a Eloquent) se le asigna el proveedor de plataforma. El panel
 * /provider/* inyecta su tenant antes de llegar aquí, y el backoffice
 * global aplica el mismo fallback en AdminCrudController.
 */
trait AssignsPlatformProvider
{
    /**
     * Nota: ninguna clase que usa este trait define su propio booted();
     * si alguna vez lo hace, este hook dejaría de registrarse.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->provider_id === null) {
                $model->provider_id = Provider::platform()->id;
            }
        });
    }
}
