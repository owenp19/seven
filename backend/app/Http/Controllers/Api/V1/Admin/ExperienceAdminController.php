<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\ExperienceResource;
use App\Repositories\BaseRepository;
use App\Repositories\ExperienceRepository;
use Illuminate\Validation\Rule;

class ExperienceAdminController extends AdminCrudController
{
    /** provider_id NOT NULL: un alta global sin proveedor va a plataforma. */
    protected bool $platformFallback = true;

    public function __construct(private readonly ExperienceRepository $experiences) {}

    protected function repository(): BaseRepository
    {
        return $this->experiences;
    }

    protected function resourceClass(): string
    {
        return ExperienceResource::class;
    }

    protected function label(): string
    {
        return 'Experience';
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('experiences', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'price_per_person' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'includes' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'user_id' => ['nullable', 'exists:users,id'],
            'rating_avg' => ['sometimes', 'numeric', 'between:0,9.9'],
            'rating_count' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
        ];
    }

    /**
     * Aprobación, ratings y vinculación de cuenta: solo plataforma.
     */
    protected function adminOnlyFields(): array
    {
        return ['status', 'user_id', 'rating_avg', 'rating_count'];
    }
}
