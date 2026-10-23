<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\Contracts\Searchable;
use App\Repositories\Concerns\AppliesTextSearch;
use Illuminate\Contracts\Pagination\Paginator;

class BusinessRepository extends BaseRepository implements Searchable
{
    use AppliesTextSearch;

    public function __construct(Business $business)
    {
        parent::__construct($business);
    }

    /**
     * Public listing with optional filters.
     *
     * @param  array{filters?: array{category?: string|null, q?: string|null, featured?: bool|null}}  $params
     */
    public function search(array $params = []): Paginator
    {
        $query = $this->query()->with('category');

        $category = $params['category'] ?? null;
        if ($category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $q = $params['q'] ?? null;
        $this->applyTextSearch($query, $q, ['name', 'description']);

        if (! empty($params['featured'])) {
            $query->where('is_verified', true);
        }

        return $query->approved()->paginate(
            $params['per_page'] ?? config('api.pagination.per_page')
        );
    }
}
