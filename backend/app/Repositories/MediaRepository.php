<?php

namespace App\Repositories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;

class MediaRepository extends BaseRepository
{
    public function __construct(Media $media)
    {
        parent::__construct($media);
    }

    /**
     * En el panel del proveedor las consultas salen limitadas a SU provider_id
     * (un archivo ajeno responde 404 en show/update/delete y nunca aparece en
     * el listado). El backoffice no aplica tenancy.
     */
    public function query(): Builder
    {
        $query = $this->model->newQuery();
        $provider = tenantProvider();

        if ($provider !== null) {
            $query->where('provider_id', $provider->id);
        }

        return $query;
    }

    public function findOrFail(int $id): Media
    {
        return $this->query()->findOrFail($id);
    }

    public function findByPath(string $path): ?Media
    {
        return $this->query()->where('file_path', $path)->first();
    }
}
