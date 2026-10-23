<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media / uploads configuration
    |--------------------------------------------------------------------------
    |
    | Validation rules for media files uploaded by providers and admins.
    | Physical files are stored in `storage/app/public` and exposed via
    | the `storage/{...}` public URL (run `php artisan storage:link`).
    |
    */

    'allowed_mimes' => 'jpg,jpeg,png,webp,avif,gif,svg',

    'max_size_kb' => 5120,

    'max_dimensions' => 'max:4096',

    'types' => ['logo', 'cover', 'gallery', 'product', 'banner', 'promotion', 'video'],

];
