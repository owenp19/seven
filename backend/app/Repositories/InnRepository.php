<?php

namespace App\Repositories;

use App\Models\Inn;

class InnRepository extends BaseRepository
{
    public function __construct(Inn $inn)
    {
        parent::__construct($inn);
    }
}
