<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Core discoverable verticals of SEVEN.
     * Kept in Spanish because they are user-facing content.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'tours', 'name' => 'Tours', 'icon' => 'boat-outline', 'description' => 'Tours y actividades para descubrir el mar Caribe', 'sort_order' => 1],
            ['slug' => 'restaurantes', 'name' => 'Restaurantes', 'icon' => 'restaurant-outline', 'description' => 'Gastronomía local y cocina del mar', 'sort_order' => 2],
            ['slug' => 'posadas-nativas', 'name' => 'Posadas Nativas', 'icon' => 'home-outline', 'description' => 'Alojamientos auténticos administrados por la comunidad raizal', 'sort_order' => 3],
            ['slug' => 'panaderias', 'name' => 'Panaderías', 'icon' => 'cafe-outline', 'description' => 'Panaderías y repostería artesanal isleña', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'description' => $category['description'],
                    // `order`/`status` son las columnas canónicas; el modelo
                    // deriva de ellas `sort_order`/`is_active`.
                    'order' => $category['sort_order'],
                    'status' => Category::ACTIVE,
                ]
            );
        }

        $this->retire($categories);
    }

    /**
     * Retira las verticales que ya no son parte de las 4 canónicas.
     *
     * Antes esto era `->delete()` y `businesses.category_id` está declarado
     * `cascadeOnDelete()`: una sola línea de seeder borraba negocios enteros
     * y dejaba sus reservas apuntando a un polymorph inexistente. Sin
     * `--force` ni dry-run, y con el seed declarado en `DatabaseSeeder`, el
     * daño era un `db:seed` de rutina.
     *
     * Ahora solo se desactivan (`status = inactive`): desaparecen del
     * catálogo público por `scopeActive()` y sus negocios quedan intactos
     * para poder reactivarlos o migrarlos. El borrado físico, si alguna vez
     * hace falta, es una decisión consciente y manual, no un efecto
     * colateral de sembrar.
     */
    private function retire(array $canonical): void
    {
        $legacy = Category::query()
            ->whereNotIn('slug', array_column($canonical, 'slug'))
            ->where(function ($query) {
                $query->where('status', '!=', Category::INACTIVE)
                    ->orWhereNull('status');
            })
            ->withCount(['businesses', 'experiences'])
            ->get();

        foreach ($legacy as $category) {
            $this->command?->warn(sprintf(
                'Categoría "%s" retirada del catálogo (%d negocio(s), %d experiencia(s) intactos).',
                $category->slug,
                $category->businesses_count,
                $category->experiences_count,
            ));

            $category->update(['status' => Category::INACTIVE]);
        }
    }
}
