<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\BakeryResource;
use App\Repositories\BakeryRepository;
use App\Repositories\BaseRepository;
use Illuminate\Validation\Rule;

class BakeryAdminController extends AdminCrudController
{
    public function __construct(private readonly BakeryRepository $bakeries) {}

    protected function repository(): BaseRepository
    {
        return $this->bakeries;
    }

    protected function resourceClass(): string
    {
        return BakeryResource::class;
    }

    protected function label(): string
    {
        return 'Bakery';
    }

    protected function with(): array
    {
        return ['products'];
    }

    protected function searchableColumns(): array
    {
        return ['name', 'description'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'provider_id' => ['required', 'exists:providers,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('bakeries', 'slug')->ignore($id)],
            'logo' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'reviews' => ['nullable', 'integer', 'min:0'],
            'opening_hours' => ['nullable', 'array'],
            'price_range' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
        ];
    }

    /**
     * Aprobación y reputación: solo plataforma.
     */
    protected function adminOnlyFields(): array
    {
        return ['status', 'rating', 'reviews'];
    }
}
