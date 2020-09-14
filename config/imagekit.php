<?php

/**
 | imagekit-adapter package configuration
 |
 */

return [

    /*
    |--------------------------------------------------------------------------
    | API Setup
    |--------------------------------------------------------------------------
    |
    | Enter your keys and endpont
    |
    */

    'public'       => env('IMAGEKIT_PUBLIC', ''),
    'private'      => env('IMAGEKIT_PRIVATE', ''),
    'endpoint'     => env('IMAGEKIT_ENDPOINT', ''),

    /*
    |--------------------------------------------------------------------------
    | Cache options
    |--------------------------------------------------------------------------
    |
    | purge_cache_update - if set to true a cache clear request is going to be made
    | on file update and delete for the given path.
    | Read more about cache here: https://docs.imagekit.io/features/caches
    |
    */

    'purge_cache_update'    => true,

    /*
    |--------------------------------------------------------------------------
    | Folder options
    |--------------------------------------------------------------------------
    |
    | include_folders - if set to true folders will also be returned when using listContents()
    |
    */

    'include_folders'       => true,

    /*
    |--------------------------------------------------------------------------
    | Extend storage
    |--------------------------------------------------------------------------
    |
    | extend_storage - if set to true will extend the file storage system, so you can define new disks 
    | using "imagekit" driver in the filesystems.php config file.
    |
    */

    'extend_storage'        => true,
];