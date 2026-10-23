<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\CategoryResource;
use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Validation\Rule;

class CategoryAdminController extends AdminCrudController
{
    public function __construct(private readonly CategoryRepository $categories) {}

    protected function repository(): BaseRepository
    {
        return $this->categories;
    }

    protected function resourceClass(): string
    {
        return CategoryResource::class;
    }

    protected function label(): string
    {
        return 'Category';
    }

    protected function searchableColumns(): array
    {
        return ['name', 'slug', 'description'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', Rule::unique('categories', 'slug')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            // `sort_order` / `is_active` se aceptan por compatibilidad con
            // el panel viejo; el modelo los realinea con `order` / `status`,
            // que son las columnas que leen el catálogo y el listado.
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
            // No nullable: un `status` NULL salía del listado sin que nada
            // lo dijera, porque `active()` lo trata como visible.
            'status' => ['sometimes', Rule::in(Category::STATUSES)],
        ];
    }
}
