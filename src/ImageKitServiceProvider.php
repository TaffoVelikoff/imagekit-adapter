<?php

namespace TaffoVelikoff\ImageKitAdapter;

use Storage;
use ImageKit\ImageKit;
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

        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/imagekit.php', 'imagekit'
        );

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if(config('imagekit.extend_storage') === true)
            Storage::extend('imagekit', function ($app) {

                // Get client
                $client = new ImageKit (
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