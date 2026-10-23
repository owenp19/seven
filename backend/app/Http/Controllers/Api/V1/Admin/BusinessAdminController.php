<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\BusinessResource;
use App\Repositories\BaseRepository;
use App\Repositories\BusinessRepository;
use Illuminate\Validation\Rule;

class BusinessAdminController extends AdminCrudController
{
    /** provider_id NOT NULL: un alta global sin proveedor va a plataforma. */
    protected bool $platformFallback = true;

    public function __construct(private readonly BusinessRepository $businesses) {}

    protected function repository(): BaseRepository
    {
        return $this->businesses;
    }

    protected function resourceClass(): string
    {
        return BusinessResource::class;
    }

    protected function label(): string
    {
        return 'Business';
    }

    protected function with(): array
    {
        return ['category'];
    }

    protected function searchableColumns(): array
    {
        return ['name', 'description'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'provider_id' => ['nullable', 'exists:providers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('businesses', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'images' => ['nullable', 'array'],
            'opening_hours' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
            'is_verified' => ['sometimes', 'boolean'],
            'rating_avg' => ['sometimes', 'numeric', 'between:0,9.9'],
            'rating_count' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
        ];
    }

    /**
     * Aprobación, verificación y ratings los controla solo la plataforma.
     */
    protected function adminOnlyFields(): array
    {
        return ['status', 'is_verified', 'rating_avg', 'rating_count'];
    }
}
