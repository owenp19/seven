<?php

namespace App\Models\Contracts;

use Illuminate\Contracts\Pagination\Paginator;

/**
 * Searchable
 *
 * Contract for the public read-facing repositories. Every public
 * listing endpoint (categories, businesses, accommodations,
 * experiences) exposes a search() method with the same shape so
 * controllers and services can rely on a common API.
 */
interface Searchable
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function search(array $params = []): Paginator;
}
