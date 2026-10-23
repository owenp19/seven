<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\V1\NewsResource;
use App\Repositories\BaseRepository;
use App\Repositories\NewsRepository;
use Illuminate\Validation\Rule;

class NewsAdminController extends AdminCrudController
{
    public function __construct(private readonly NewsRepository $news) {}

    protected function repository(): BaseRepository
    {
        return $this->news;
    }

    protected function resourceClass(): string
    {
        return NewsResource::class;
    }

    protected function label(): string
    {
        return 'News';
    }

    protected function with(): array
    {
        return ['provider'];
    }

    protected function searchableColumns(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected function rules(bool $updating, ?int $id = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // Unico en toda la tabla, no solo entre publicadas: un borrador reserva su
            // slug para que al publicar no colisione. Al editar se excluye a si
            // misma para que reenviar el mismo slug no choque consigo mismo.
            'slug' => [
                $updating ? 'sometimes' : 'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('news', 'slug')->ignore($id),
            ],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'kind' => ['sometimes', Rule::in(['news', 'event', 'tip', 'promotion'])],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['published', 'draft', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Publicar, destacar o elegir el proveedor de una noticia: solo
     * plataforma. El tenant ya fuerza su propio provider_id.
     */
    protected function adminOnlyFields(): array
    {
        return ['status', 'is_featured', 'provider_id'];
    }
}
