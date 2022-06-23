<?php
	
namespace TaffoVelikoff\ImageKitAdapter;

use DateTime;
use ImageKit\ImageKit;
use League\Flysystem;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use TaffoVelikoff\ImageKitAdapter\Exceptions\ImageKitConfigurationException;


class ImagekitAdapter implements Flysystem\FilesystemAdapter {

    protected $client;
    protected $options;

    public function __construct(ImageKit $client, $options = []) {
        $this->client = $client;
        $this->options = $options;
    }

    public function getClient(): ImageKit
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $path): bool
    {
        $location = $this->getFileFolderNames($path);

        $file = $this->client->listFiles([
            'name'          => $location['file'],
            'includeFolder' => true
        ]);

        if(empty($file->success))
            return false;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function directoryExists(string $path): bool
    {
        $location = $this->getFileFolderNames($path);

        $directory = $this->client->listFiles([
            'name'          => $location['file'],
            'includeFolder' => true
        ]);

        if(empty($directory->success))
            return false;

        if($directory->success[0]->type != 'folder')
            throw new UnableToCheckDirectoryExistence;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $path, $contents, Config $config): void
    {
        if($this->upload($path, $contents, $config) == false)
            throw new UnableToWriteFile('Unable to write file to location '.$path);
            
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        if($this->upload($path, $contents, $config) == false)
            throw new UnableToWriteFile('Unable to write file to location '.$path);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path): string
    {
        return stream_get_contents($this->readStream($path));
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        if(!strlen($path))
            throw new UnableToReadFile('Path should not be empty.');

        $file = $this->searchFile($path);

        return @fopen($file->url, 'rb');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path): void
    {

        if(!strlen($path))
            throw new UnableToReadFile('Path should not be empty.');

        $file = $this->searchFile($path);

        // Make a purge cache request
        if(isset($this->options['purge_cache_update']) && $this->options['purge_cache_update']['enabled'] === true) {
            
            if(!isset($this->options['purge_cache_update']['endpoint_url']))
                throw new ImageKitConfigurationException('Purge cache option is enabled, but endpoint url is not set.');

            $this->client->purgeCacheApi($this->options['purge_cache_update']['endpoint_url'].'/'.$path);
        
        }

        $this->client->deleteFile($file->fileId);
        
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $path): void
    {
        $delete = $this->client->deleteFolder($path);

        if($delete->err != null)
            throw new UnableToReadFile('Directory not found.');
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path, Config $config): void
    {
        $create = $this->client->createFolder($path);

        if(!empty($create->err))
            throw new UnableToCreateDirectory;
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    /**
     * @inheritDoc
     */
    public function visibility(string $path): FileAttributes
    {
        throw UnableToSetVisibility::atLocation($path, 'Adapter does not support visibility controls.');
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes($path, null, null, null, (new FinfoMimeTypeDetector())->detectMimeTypeFromPath($path));
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $path): FileAttributes
    {
        $file = $this->searchFile($path);

        $meta = $this->client->getFileDetails($file->fileId);

        return new FileAttributes($path, null, null, strtotime($meta->success->updatedAt));
    }

    /**
     * @inheritDoc
     */
    public function fileSize(string $path): FileAttributes
    {

        $file = $this->searchFile($path);

        $meta = $this->client->getMetaData($file->fileId);

        return new FileAttributes($path, $meta->success->size ?? null );

    }

    /**
     * {@inheritdoc}
     */
    public function listContents(string $path, bool $deep = false): iterable
    {

        $list = $this->client->listFiles([
            'path'          => $path,
            'includeFolder' => $deep
        ]);

        foreach ($list->success as $item) {
            yield $this->normalizeObject($item);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy(string $source, string $destination, Config $config): void
    {

        $source = $this->searchFile($source);

        if($source->type == 'file') {
            $this->client->copyFile($source->filePath, $destination);
        } else {
            $this->client->copyFolder($source->filePath, $destination);
        }
    }

    /**
     * @inheritDoc
     */
    public function move(string $source, string $destination, Config $config): void
    {

        $asset = $this->searchFile($source);

        if($asset->type == 'folder') {
            $this->client->moveFolder($source, $destination);
        } else {
            $this->client->moveFile($source, $destination);
        }

    }

    /**
     * Transform $path to file name and folder name.
     * @param string $path Path to file
     *
     * @return array|false
     */
    public function getFileFolderNames(string $path) 
    {

        if(!$path)
            return false;

        $folder = '/';
        $fileName = $path;

        // Check for folders in path (file name)
        $folders = explode('/', $path);
        if(count($folders) > 1) {
            $fileName = end($folders);
            $folder = str_replace('/'.end($folders), '', $path);
        }

        return [
            'file'  => $fileName,
            'directory' => $folder
        ];
    }


    /**
     * @param array $item Files and folders from listContents.
     *
     * @return StorageAttributes
     */
    protected function normalizeObject(object $item): StorageAttributes
    {
        return match ($item->type) {

            'folder' => new DirectoryAttributes(
                $item->folderPath,
                null,
                strtotime($item->updatedAt),
                ['id'   => $item->folderId]
            ),

            'file' => new FileAttributes(
                $item->filePath,
                $item->size,
                null,
                strtotime($item->updatedAt),
                $item->mime ?? null,
                ['id'   => $item->fileId]
            )

        };
    }

    /**
     * Upload a file to Imagekit.io
     * @param string $path
     * @param resource|string $contents
     *
     * @return mixed file metadata
     */
    protected function upload(string $path, $contents) 
    {

        $location = $this->getFileFolderNames($path);

        if($location === false)
            return false;

        // If not resource or URL - base64 encode
        if(!is_resource($contents) && !filter_var($contents, FILTER_VALIDATE_URL))
            $contents = base64_encode($contents);

        $upload = $this->client->upload([
            'file'              => $contents,
            'fileName'          => $location['file'],
            'useUniqueFileName' => false,
            'folder'            => $location['directory']
        ]);

        return $upload;

    }

    /**
     * Search for a file or directory by name/path.
     * @param string $path
     *
     * @return array
     */
    public function searchFile($path) 
    {

        $location = $this->getFileFolderNames($path);

        // Get file from old path
        $file = $this->client->listFiles([
            'name'          => $location['file'] ?? '',
            'path'          => $location['directory'] ?? '',
            'includeFolder' => true
        ]);

        if(empty($file->success))
            throw new UnableToReadFile('File or directory not found.');

        return $file->success[0];

    }

    /**
     * Get the full url.
     * @param string $path
     *
     * @return array
     */
    public function getUrl(string $path): string 
    {

        return $this->client->url([
            "path" => $path,
        ]);

    }

}