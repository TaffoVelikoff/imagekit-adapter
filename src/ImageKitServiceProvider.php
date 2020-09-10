<?php

namespace TaffoVelikoff\ImageKitAdapter;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ImageKitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/imagekit.php' => config_path('imagekit.php'),
        ], 'imagekit');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

       Storage::extend('imagekit', function () {

            // Get client
            $client = new \TaffoVelikoff\ImageKitAdapter\Client(
                config('imagekit.public'),
                config('imagekit.private'),
                config('imagekit.endpoint')
            );

            // Get adapter
            $adapter = new ImagekitAdapter($client);

            // Return filesystem
            return new Filesystem($adapter);
        });
    }

}