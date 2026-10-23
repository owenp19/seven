<?php

namespace App\Repositories;

use App\Models\Banner;

class BannerRepository extends BaseRepository
{
    public function __construct(Banner $banner)
    {
        parent::__construct($banner);
    }
}
