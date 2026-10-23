<?php

namespace App\Repositories;

use App\Models\Tour;

class TourRepository extends BaseRepository
{
    public function __construct(Tour $tour)
    {
        parent::__construct($tour);
    }
}
