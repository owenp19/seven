<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina el morph duplicado `service_type` / `service_id` de `bookings`.
     *
     * `bookable_type` / `bookable_id` ya cubren las seis verticales
     * reservables (Business, Accommodation, Experience, Tour, Restaurant,
     * Inn) y son las columnas que BookingService rellena. El par `service_*`
     * se añadió como "future-proof" en 2026_09_20_171038 y quedó sin usar:
     *
     *  - Booking::service() llamaba a `morphTo()` sin argumentos, que
     *    resuelve contra `type`/`id`, no contra `service_*`; el morph nunca
     *    iba a funcionar y no se notaba porque nadie lo invocaba.
     *  - Un booking creado solo con `service` quedaba con provider_id null,
     *    así que no se enviaba la notificación al proveedor.
     *  - Dos morphs al mismo concepto invites a que se escriban uno sí y
     *    otro no, y a que se desincronicen sin que nada lo detecte.
     *
     * La comprobación inicial aborta la migración si alguna fila tuviera
     * datos: es un guard para que nadie descubra por un 500 que sí los
     * había, y no un borrado silencioso.
     */
    public function up(): void
    {
        $populated = DB::table('bookings')
            ->where(fn ($query) => $query->whereNotNull('service_type')->orWhereNotNull('service_id'))
            ->count();

        if ($populated > 0) {
            throw new RuntimeException(
                "bookings contiene {$populated} fila(s) con service_type/service_id. "
                .'Estas columnas nunca se escribieron, así que casi seguro es basura: '
                .'revísalas y publégalas en bookable_type/bookable_id antes de migrar.',
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropMorphs('service');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->nullableMorphs('service');
        });
    }
};
