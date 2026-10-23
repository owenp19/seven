<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unifica la vinculación de servicios al provedor dueño:
     * todos los bookables (businesses, accommodations, experiences)
     * ganan un provider_id, igual que tours/restaurants/inns/bakeries,
     * para que el panel del dueño y los paneles de proveedor del futuro
     * resuelvan al mismo modelo Provider.
     */
    public function up(): void
    {
        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('provider_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $blueprint->index('provider_id');
            });
        }

        foreach (['accommodations', 'experiences'] as $table) {
            $this->backfill($table);
        }
    }

    public function down(): void
    {
        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('provider_id');
            });
        }
    }

    /** Asigna el provider existente cuyo user_id coincide con el owner del servicio. */
    private function backfill(string $table): void
    {
        $providersByUser = collect(DB::table('providers')->pluck('id', 'user_id'))
            ->filter(fn ($id, $userId) => $userId !== null);

        foreach ($providersByUser as $userId => $providerId) {
            DB::table($table)
                ->where('user_id', $userId)
                ->whereNull('provider_id')
                ->update(['provider_id' => $providerId]);
        }
    }
};
