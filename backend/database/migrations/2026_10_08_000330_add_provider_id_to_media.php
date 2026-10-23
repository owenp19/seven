<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1. Vuelve nullable provider_id en `media` y lo vuelca desde el objeto
     *    morph al que está anclado: las verticales marketplace (que ya lo
     *    tienen) y sus imágenes/productos (que lo heredan del padre), además
     *    del propio provider. Los que no resuelven dueño (categories, banners)
     *    quedan NULL y son de la plataforma.
     * 2. Índice para que el panel del proveedor consulte sus archivos sin
     *    barrer los de los demás.
     *
     * down() solo retira la columna y el índice; el backfill de datos no se
     * revierte.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $blueprint) {
            $blueprint->foreignId('provider_id')
                ->nullable()
                ->after('id');
            $blueprint->index('provider_id');
            $blueprint->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
        });

        $rows = DB::table('media')->get();

        foreach ($rows as $row) {
            if ($row->provider_id !== null || ! class_exists($row->model_type)) {
                continue;
            }

            $model = $row->model_type::find($row->model_id);

            $providerId = $this->providerIdFor($model);

            if ($providerId !== null) {
                DB::table('media')->where('id', $row->id)->update(['provider_id' => $providerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['provider_id']);
            $blueprint->dropIndex(['provider_id']);
            $blueprint->dropColumn('provider_id');
        });
    }

    /**
     * Dueño del recurso morph al que cuelga el archivo. Las verticales y el
     * provider lo traen directo; imágenes/productos lo heredan del padre.
     */
    private function providerIdFor(?object $model): ?int
    {
        if ($model === null) {
            return null;
        }

        $parentRelation = [
            'TourImage' => 'tour',
            'InnRoom' => 'inn',
            'RestaurantProduct' => 'restaurant',
            'BakeryProduct' => 'bakery',
        ][class_basename($model)] ?? null;

        $target = $parentRelation !== null ? $model->{$parentRelation} : $model;

        return $target?->provider_id ?? null;
    }
};
