<?php

namespace App\Repositories;

use App\Models\Bakery;

class BakeryRepository extends BaseRepository
{
    public function __construct(Bakery $bakery)
    {
        parent::__construct($bakery);
    }
}
