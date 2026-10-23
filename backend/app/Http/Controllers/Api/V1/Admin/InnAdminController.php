<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\InnResource;
use App\Repositories\BaseRepository;
use App\Repositories\InnRepository;
use Illuminate\Validation\Rule;

class InnAdminController extends AdminCrudController
{
    public function __construct(private readonly InnRepository $inns) {}

    protected function repository(): BaseRepository
    {
        return $this->inns;
    }

    protected function resourceClass(): string
    {
        return InnResource::class;
    }

    protected function label(): string
    {
        return 'Inn';
    }

    protected function with(): array
    {
        return ['rooms'];
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('inns', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
            'services' => ['nullable', 'array'],
            'availability' => ['sometimes', 'boolean'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
        ];
    }

    /**
     * Aprobación y reputación: solo plataforma.
     */
    protected function adminOnlyFields(): array
    {
        return ['status', 'rating'];
    }
}
