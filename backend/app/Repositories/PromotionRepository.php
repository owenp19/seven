<?php

namespace App\Repositories;

use App\Models\Promotion;

class PromotionRepository extends BaseRepository
{
    public function __construct(Promotion $promotion)
    {
        parent::__construct($promotion);
    }
}
