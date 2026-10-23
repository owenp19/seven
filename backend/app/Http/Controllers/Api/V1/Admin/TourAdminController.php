<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\TourResource;
use App\Repositories\BaseRepository;
use App\Repositories\TourRepository;
use Illuminate\Validation\Rule;

class TourAdminController extends AdminCrudController
{
    public function __construct(private readonly TourRepository $tours) {}

    protected function repository(): BaseRepository
    {
        return $this->tours;
    }

    protected function resourceClass(): string
    {
        return TourResource::class;
    }

    protected function label(): string
    {
        return 'Tour';
    }

    protected function with(): array
    {
        return ['images', 'includes', 'recommendations'];
    }

    protected function searchableColumns(): array
    {
        return ['name', 'subtitle', 'description'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'provider_id' => ['required', 'exists:providers,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tours', 'slug')->ignore($id)],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'integer', 'min:0'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'reviews' => ['nullable', 'integer', 'min:0'],
            'cover_image' => ['nullable', 'string', 'max:500'],
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
