<?php

namespace App\Repositories;

use App\Models\Accommodation;
use App\Models\Contracts\Searchable;
use App\Repositories\Concerns\AppliesTextSearch;
use Illuminate\Contracts\Pagination\Paginator;

class AccommodationRepository extends BaseRepository implements Searchable
{
    use AppliesTextSearch;

    public function __construct(Accommodation $accommodation)
    {
        parent::__construct($accommodation);
    }

    /**
     * Public listing with optional filters.
     *
     * @param  array{filters?: array{guests?: int|null, q?: string|null}}  $params
     */
    public function search(array $params = []): Paginator
    {
        $query = $this->query();

        $guests = $params['guests'] ?? null;
        if ($guests) {
            $query->where('capacity', '>=', $guests);
        }

        $q = $params['q'] ?? null;
        $this->applyTextSearch($query, $q, ['name', 'description']);

        return $query->approved()->paginate(
            $params['per_page'] ?? config('api.pagination.per_page')
        );
    }
}
