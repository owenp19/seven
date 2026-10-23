<?php

namespace App\Repositories;

use App\Models\Contracts\Searchable;
use App\Models\Experience;
use App\Repositories\Concerns\AppliesTextSearch;
use Illuminate\Contracts\Pagination\Paginator;

class ExperienceRepository extends BaseRepository implements Searchable
{
    use AppliesTextSearch;

    public function __construct(Experience $experience)
    {
        parent::__construct($experience);
    }

    /**
     * Public listing with optional filters.
     *
     * @param  array{guests?: int|null, category?: string|null, q?: string|null}  $params
     */
    public function search(array $params = []): Paginator
    {
        $query = $this->query();

        $guests = $params['guests'] ?? null;
        if ($guests) {
            $query->where('max_capacity', '>=', $guests);
        }

        $category = $params['category'] ?? null;
        if ($category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $q = $params['q'] ?? null;
        $this->applyTextSearch($query, $q, ['name', 'description']);

        return $query->approved()->paginate(
            $params['per_page'] ?? config('api.pagination.per_page')
        );
    }
}
