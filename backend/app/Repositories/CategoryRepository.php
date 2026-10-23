<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $category)
    {
        parent::__construct($category);
    }

    /**
     * Categorías visibles, ordenadas por su posición de display.
     *
     * Filtra y ordena por las columnas canónicas (`status` / `order`).
     * Antes leía `is_active` / `sort_order`, las columnas legacy que la
     * migración 2026_09_20_171031 dejó junto a las nuevas: un PATCH de
     * `status` no cambiaba nada de lo que esta consulta miraba.
     */
    public function activeCategories()
    {
        return $this->query()
            ->active()
            ->orderBy('order')
            ->orderBy('id')
            ->withCount('businesses');
    }
}
