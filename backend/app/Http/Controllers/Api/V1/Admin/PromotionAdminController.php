<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\PromotionResource;
use App\Models\Promotion;
use App\Repositories\BaseRepository;
use App\Repositories\PromotionRepository;
use Illuminate\Validation\Rule;

class PromotionAdminController extends AdminCrudController
{
    public function __construct(private readonly PromotionRepository $promotions) {}

    protected function repository(): BaseRepository
    {
        return $this->promotions;
    }

    protected function resourceClass(): string
    {
        return PromotionResource::class;
    }

    protected function label(): string
    {
        return 'Promotion';
    }

    protected function searchableColumns(): array
    {
        return ['title', 'description'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'provider_id' => ['required', 'exists:providers,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'discount' => ['nullable', 'integer', 'between:0,100'],
            'price' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::in(Promotion::STATUSES)],
        ];
    }

    /**
     * Publicar/despublicar una promoción es potestad del backoffice.
     */
    protected function adminOnlyFields(): array
    {
        return ['status'];
    }
}
