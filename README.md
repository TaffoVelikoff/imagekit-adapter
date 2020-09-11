[UNDER DEVELOPMENT]

# Laravel filesystem adapter for the ImageKit API

A [Flysystem](https://flysystem.thephpleague.com/) adapter for [ImageKit](https://imagekit.io/).
## Contents

[⚙️ Installation](#installation)

[🛠️ Setup](#setup)

[👩‍💻 Usage](#usage)

[📐 Configuration](#configuration)


## Installation

You can install the package via composer:

``` bash
composer require taffovelikoff/imagekit-adapter
```

## Setup

First you will need to sing up for an [ImageKit](https://imagekit.io/) account. Then go [https://imagekit.io/dashboard#developers](https://imagekit.io/dashboard#developers) to get your public key, private key and url endpoint. Add the following to your .env file:

```
IMAGEKIT_PUBLIC=your_public_key
IMAGEKIT_PRIVATE=your_public_key
IMAGEKIT_ENDPOINT=https://ik.imagekit.io/your_id
```
If you prefer you can publish the config file:

```
php artisan vendor:publish --tag=imagekit
```
## Usage

Go to `config/filesystems.php ` and create a new disk (or change the driver of one to 'imagekit'):
```php
'disks' => [
    ...
    'imagekit' => [
        'driver'    => 'imagekit',
    ]
],
```
```php
use Storage;

// Upload file (second argument can be a url, file or base64)
Storage::disk('imagekit')->put('filename.jpg', 'http://mysite.com/my_image.com');

// Get file
Storage::disk('imagekit')->get('filename.jpg');

// Delete file
Storage::disk('imagekit')->delete('filename.jpg');

// List all files 
Storage::disk('imagekit')->listContents('', false); // listContents($directoryName, $recursive)
```
## Configuration
If you publish the config file you can change a few things:
```
'purge_cache_update'    => true,
'include_folders'       => true,
```
* purge_cache_update - if set to true a cache clear request is going to be made for he given path. Read more here: [https://docs.imagekit.io/features/caches](https://docs.imagekit.io/features/caches).
* include_folders - if set to true folders will also be returned when using listContents()

## License
[MIT](https://choosealicense.com/licenses/mit/)