<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\BannerResource;
use App\Repositories\BannerRepository;
use App\Repositories\BaseRepository;
use Illuminate\Validation\Rule;

class BannerAdminController extends AdminCrudController
{
    public function __construct(private readonly BannerRepository $banners) {}

    protected function repository(): BaseRepository
    {
        return $this->banners;
    }

    protected function resourceClass(): string
    {
        return BannerResource::class;
    }

    protected function label(): string
    {
        return 'Banner';
    }

    protected function searchableColumns(): array
    {
        return ['title', 'subtitle', 'description'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'url', 'max:500'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'order' => ['sometimes', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
