# Flysystem adapter for the ImageKit API

A [Flysystem](https://flysystem.thephpleague.com/) adapter for [ImageKit](https://imagekit.io/).
This package used to be Laravel only, but it can now be used in any php project! If you are using an older version of this package in a Laravel app, please read the "Usage in Laravel" section.

## Contents

[⚙️ Installation](#installation)

[🛠️ Setup](#setup)

[👩‍💻 Usage](#usage)

[🚀 Usage in Laravel](#usage-in-laravel)

[👊 Contributing](#contributing)

[📄 License](#license)


## Installation

You can install the package via composer:

``` bash
composer require taffovelikoff/imagekit-adapter
```

## Setup

First you will need to sing up for an [ImageKit](https://imagekit.io/) account. Then you can go to [https://imagekit.io/dashboard#developers](https://imagekit.io/dashboard#developers) to get your public key, private key and url endpoint.

## Usage

```php
use ImageKit\ImageKit;
use League\Flysystem\Filesystem;
use TaffoVelikoff\ImageKitAdapter\ImageKitAdapter;

// Client
$client = new ImageKit (
    'your_public_key',
    'your_private_key',
    'your_endpoint_url' // Should look something like this https://ik.imagekit.io/qvkc...
);

// Adapter
$adapter = new ImagekitAdapter($client);

// Filesystem
$fsys = new Filesystem($adapter);

// Check if file exists example
$file = $fsys->fileExists('default-image.jpg');
```
If you need to purge the cache after a file was updated/deleted you can add "purge_cache" to the $options array of the adapter.

```php
$adapter = new ImagekitAdapter($client, $options = [
    'purge_cache_update'    => [
        'enabled'       => true,
        'endpoint_url'  => 'your_endpoint_url'
    ]
]);
```
This will create a purge cache request. You can read more here: [https://docs.imagekit.io/features/cache-purging](https://docs.imagekit.io/features/cache-purging)

## Usage in Laravel

You can create a new driver by extending the Storage in the `boot()` method of `AppServiceProvider`.

```php
public function boot()
{
    Storage::extend('imagekit', function ($app, $config) {
        $adapter = new ImagekitAdapter(

            new ImageKit(
                $config['public_key'],
                $config['private_key'],
                $config['endpoint_url']
            ),

            $options = [ // Optional
                'purge_cache_update'    => [
                    'enabled'       => true,
                    'endpoint_url'  => 'your_endpoint_url'
                 ]
            ] 

        );

        return new FilesystemAdapter(
            new Filesystem($adapter, $config),
            $adapter,
            $config
        );
    });
}
```
Then create a new disk in `config/filesystems.php`:
```php
'imagekit' => [
    'driver' => 'imagekit',
    'public_key' => env('IMAGEKIT_PUBLIC_KEY'),
    'private_key' => env('IMAGEKIT_PRIVATE_KEY'),
    'endpoint_url' => env('IMAGEKIT_ENDPOINT_URL')
],
```
Don't forget to add your keys in `.env`:
```php
IMAGEKIT_PUBLIC_KEY = your-public-key
IMAGEKIT_PRIVATE_KEY = your-private-key
IMAGEKIT_ENDPOINT_URL = your-endpint-url
```
And now you can use Laravel's Storage facade:
```php
Storage::disk('imagekit')->put('test.txt', 'This is a test file.');

return response(Storage::disk('imagekit')->get('test.txt'));
```
If you already use an older version of `taffovelikoff/imagekit-adapter` in your Laravel app you most likely published the configuration file `config/imagekit.php`. It was possible to set a few options there:
```php
return [
    'purge_cache_update'    => true,
    'extend_storage'        => true,
];
```
The `extend_storage => true` setting automatically expanded the Storage facade and created 'imagekit' driver. If you were using that option you need to manually add the new driver in `AppServiceProvider` like the example above.

If the `purge_cache_update` setting was set to `true` a cache purge request was made when deleting/updating a file. In order tо keep this functionality all you need to do now is add `purge_cache_update` parameter in the options of the ImageKitAdapter when extending the storage.


## Contributing
Pull requests are welcome. Please feel free to lodge any issues or feedback as discussion points.

## License
[MIT](https://choosealicense.com/licenses/mit/)