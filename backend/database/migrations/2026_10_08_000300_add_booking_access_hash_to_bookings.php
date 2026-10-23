<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Credencial de acceso por reserva (v1 anónima).
     *
     * Cada reserva recibe un `booking_access_token` criptográfico que se
     * entrega UNA sola vez en la respuesta de creación; aquí solo vive su
     * SHA-256, de modo que ni la base de datos, ni los logs, ni los
     * Resources exponen el secreto. Consultar/cancelar por `booking_code`
     * exige ese token (cabecera X-Booking-Token).
     *
     * Las filas legacy (NULL) quedan sin propietario verificable: dejan de
     * ser accesibles por el código público y siguen consultables únicamente
     * por admin/proveedor. No se borran.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_access_hash', 64)->nullable()
                ->comment('SHA-256 del booking_access_token entregado una vez al crear la reserva (nunca se guarda el token).')
                ->after('install_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_access_hash');
        });
    }
};
