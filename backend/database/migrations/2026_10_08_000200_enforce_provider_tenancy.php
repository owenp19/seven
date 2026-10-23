<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Endurece el tenancy por proveedor antes de abrir el panel /provider/*:
     *
     *  1. Crea el proveedor de plataforma ("gestión directa" de SEVEN) que
     *     absorbe los servicios heredados que no tienen dueño.
     *  2. Reasigna a ese proveedor los servicios con provider_id NULL
     *     (businesses, accommodations y experiences: el backfill anterior
     *     no encontró filas porque providers.user_id estaba vacío).
     *  3. Recalcula bookings.provider_id desde el bookable, para que las
     *     reservas heredadas queden con el proveedor que las atiende.
     *  4. Vuelve NOT NULL provider_id en las tres verticales clásicas
     *     (tours, restaurants, inns y bakeries ya lo eran).
     *  5. Índices para las consultas del panel: único en providers.user_id
     *     (un usuario = un proveedor), [provider_id, status] en las tres
     *     verticales clásicas y [provider_id, start_at] en bookings.
     *
     * El backfill de datos no es reversible: down() solo retira índices y
     * vuelve a permitir NULL en las columnas.
     */
    private const PLATFORM_NAME = 'SEVEN (gestión directa)';

    public function up(): void
    {
        $platformId = $this->platformProviderId();

        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            DB::table($table)->whereNull('provider_id')->update(['provider_id' => $platformId]);
        }

        $this->backfillBookingsProvider();

        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            // MySQL no permite NOT NULL mientras la FK diga ON DELETE SET
            // NULL (error 1830), así que se redefine como RESTRICT: borrar
            // un proveedor con servicios queda rechazado por la BD. En
            // SQLite el drop por columnas se resuelve en el rebuild de la
            // misma alteración.
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['provider_id']);
                $blueprint->foreignId('provider_id')->nullable(false)->change();
                $blueprint->foreign('provider_id')->references('id')->on('providers')->restrictOnDelete();
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index(['provider_id', 'status']);
            });
        }

        Schema::table('providers', function (Blueprint $blueprint) {
            $blueprint->unique('user_id');
        });

        Schema::table('bookings', function (Blueprint $blueprint) {
            $blueprint->index(['provider_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['provider_id', 'start_at']);
        });

        Schema::table('providers', function (Blueprint $blueprint) {
            $blueprint->dropUnique(['user_id']);
        });

        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['provider_id', 'status']);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['provider_id']);
                $blueprint->foreignId('provider_id')->nullable()->change();
                $blueprint->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
            });
        }
    }

    /**
     * Fila de plataforma: la gestionada directamente por SEVEN (servicios
     * heredados sin cuenta de proveedor). Clave de idempotencia: business_name,
     * igual que usa Provider::platform() en tiempo de ejecución.
     */
    private function platformProviderId(): int
    {
        $query = DB::table('providers')->where('business_name', self::PLATFORM_NAME);

        if ($query->exists()) {
            return (int) $query->value('id');
        }

        return (int) DB::table('providers')->insertGetId([
            'business_name' => self::PLATFORM_NAME,
            'type' => 'tour_operator',
            'status' => 'approved',
            'verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * bookings.provider_id se deriva del bookable al crear la reserva; las
     * reservas anteriores a esa regla quedaron NULL. Se recalcula desde la
     * tabla del bookable (subconsulta correlacionada, compatible MySQL y SQLite).
     */
    private function backfillBookingsProvider(): void
    {
        $types = DB::table('bookings')
            ->whereNull('provider_id')
            ->distinct()
            ->pluck('bookable_type');

        foreach ($types as $type) {
            if (! class_exists($type)) {
                continue;
            }

            $table = (new $type)->getTable();

            DB::update(
                "UPDATE bookings
                 SET provider_id = (
                     SELECT service.provider_id FROM {$table} service
                     WHERE service.id = bookings.bookable_id
                 )
                 WHERE bookable_type = ? AND provider_id IS NULL",
                [$type]
            );
        }
    }
};
