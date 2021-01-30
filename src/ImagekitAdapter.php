<?php
	
namespace TaffoVelikoff\ImageKitAdapter;

use DateTime;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util\MimeType;

class ImagekitAdapter extends AbstractAdapter {

	use NotSupportingVisibilityTrait;

    protected $client;

    public function __construct(\ImageKit\ImageKit $client) {
        $this->client = $client;
    }
	
    public function getUrl($path)
    {
        return $this->client->url([
                "path" => $path,
            	]);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        // Make a purge cache request
        if(config('imagekit.purge_cache_update') === true)
            $this->client->purgeCacheApi(config('imagekit.endpoint').'/'.$path);

        return $this->upload($path, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return parent::updateStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newPath): bool
    {   
        // Get old file
        $oldFile = $this->searchFile($path);
        $oldFileUrl = $oldFile->url;

        // Upload new file
        $new = $this->upload($newPath, $oldFileUrl);

        // Delete old file
        $this->client->deleteFile($oldFile->fileId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newPath): bool
    {
        $oldFile = $this->searchFile($path);
        $oldFileUrl = $oldFile->url;

        // Upload new file
        $new = $this->upload($newPath, $oldFileUrl);

        if($new->err != null)
            return false;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path): bool
    {
        // Find file
        $file = $this->searchFile($path);

        // Make a purge cache request
        if(config('imagekit.purge_cache_update') === true)
            $this->client->purgeCacheApi(config('imagekit.endpoint').'/'.$path);

        // Delete file
        $delete = $this->client->deleteFile($file->fileId);

        if($delete->err == null)
            return true;

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname): bool
    {
        // Get the folder
        $folder = $this->client->listFiles([
            'name'          => $dirname,
            'includeFolder' => true
        ]);

        if(count($folder->success) == 0)
            return false;

        // Delete folder
        $this->client->deleteFile($folder->success[0]->folderId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        // The ImageKit API does not offer an endpoint for this acion currently.
        // A workaround is to upload an "empty" file, the upload endpoint can create
        // folders when the "folder" parameter is set.

        // Upload file
        $upload = $this->client->upload([
            'file'              => 'xxx',
            'fileName'          => 'empty',
            'useUniqueFileName' => false,
            'folder'            => $dirname
        ]);

        return $upload;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        // File name and directory
        $filePath = $this->getFileFolderName($path);

        // Search for file
        $file = $this->client->listFiles([
            'name'          => $filePath['fileName'],
            'path'          => $filePath['directory'],
            'includeFolder' => true
        ]);

        // Does NOT exist
        if(count($file->success) == 0)
            return false;

        // Exists
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $filePath = $this->getFileFolderName($path);

        // Get file
        $file = $this->client->listFiles([
            'name'          => $filePath['fileName'],
            'includeFolder' => true
        ]);

        // Convert result to array
        $data = (array)$file;

        // Return file url
        return [
            'contents'  => $data['success'][0],
            'stream'    => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->read($path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false): array
    {

        $list = $this->client->listFiles([
            'name'          => $directory,
            'includeFolder' => config('imagekit.include_folders')
        ]);

        // If not recursive remove files
        if($recursive === false) {
            foreach ($list->success as $key => $e) {

                // Get path parts
                if(isset($e->filePath)) {
                    $pathParts = explode('/', $e->filePath);
                } else {
                    $pathParts = explode('/', $e->folderPath);
                }

                // Get directory name
                end($pathParts);
                $dirName = prev($pathParts);

                if($dirName != $directory) {
                    unset($list->success[$key]);
                }
            }
        }

        $files = array_map(function($e) use($recursive, $directory) {

            $dirName = '';

            // Get path parts
            if(isset($e->filePath)) {
                $pathParts = explode('/', $e->filePath);
                $filePath = $e->filePath; 
            } else {
                $pathParts = explode('/', $e->folderPath);
                $filePath = $e->folderPath;
            }

            // Get directory name
            end($pathParts);
            $dirName = prev($pathParts);

            return [
                'path'      => $filePath,
                'dirname'   => $dirName,
            ];
            
        }, $list->success);

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        // Search for file
        $file = $this->searchFile($path);

        // Get data from file search
        $return = (array)$file;
        
        $fileId = $file->fileId;

        // Get timestamp
        $createdAt = $file->createdAt;
        $date = new DateTime($createdAt);
        $timestamp = $date->getTimestamp();
        $return['timestamp'] = $timestamp;

        // Get mimetype
        $return['mimetype'] = MimeType::detectByFilename($path);

        // Get more meta data
        $moreData = $this->client->getMetaData($fileId);
        $return = array_merge((array)$moreData->success, $return);

        // Return all
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function applyPathPrefix($path): string
    {
        $path = parent::applyPathPrefix($path);

        return '/'.trim($path, '/');
    }

    /**
     * @param string $path
     * @param resource|string $contents
     *
     * @return array|false file metadata
     */
    protected function upload(string $path, $contents)
    {
        $file = $this->getFileFolderName($path);
        
        if($file === false)
            return false;

        // Upload file
        $upload = $this->client->upload([
            'file'              => $contents,
            'fileName'          => $file['fileName'],
            'useUniqueFileName' => false,
            'folder'            => $file['directory']
        ]);


        return $upload;
    }

    /**
     * @param string $path
     *
     * @return array|false
     *
     * Transform $path to file name and folder name
     */
    public function getFileFolderName(string $path) {

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
            'fileName'  => $fileName,
            'directory' => $folder
        ];
    }

     /**
     * @param string $path
     *
     * Search for a file or directory by name/path
     */
    public function searchFile($path) {
        $filePath = $this->getFileFolderName($path);

        // Get file from old path
        $file = $this->client->listFiles([
            'name'    => $filePath['fileName'],
            'path'    => $filePath['directory']
        ]);

        return $file->success[0];
    }

}
