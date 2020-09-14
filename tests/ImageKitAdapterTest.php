<?php

namespace TaffoVelikoff\ImageKitAdapter\Tests;

use ImageKit\ImageKit;
use Prophecy\Argument;
use League\Flysystem\Config;
use Prophecy\PhpUnit\ProphecyTrait;
use League\Flysystem\FileExistsException;
use TaffoVelikoff\ImageKitAdapter\ImagekitAdapter;

class ImageKitAdapterTest extends \Orchestra\Testbench\TestCase {

    use ProphecyTrait;

    protected $client;
    protected $adapter;

    public function setUp(): void {
        parent::setUp();

        $this->client = $this->prophesize(ImageKit::class);
        $this->adapter = new ImagekitAdapter($this->client->reveal());
    }

    /** @test */
    public function testCanRead() {
        
        // Mock api call
        $this->client->listFiles(Argument::type('array'))->willReturn([
            'err' => null,
            'success'   => [
                0 => [
                    'type' => 'file',
                    'name' => 'ineedu.jpg',
                    'createdAt' => '2020-09-10T15:07:06.190Z',
                    'fileId' => '5f5a411a7374d315559daaf0',
                    'tags' => null,
                    'customCoordinates' => null,
                    'isPrivateFile' => false,
                    'url' => 'https://ik.imagekit.io/test/ineedu.jpg',
                    'thumbnail' => 'https://ik.imagekit.io/test/tr:n-media_library_thumbnail/ineedu.jpg',
                    'fileType' => 'image',
                    'filePath' => '/ineedu.jpg'
                ]
            ]
        ]);

        // What do we  expect?
        $expected = [
            "contents" => [
                "type" => "file",
                "name" => "ineedu.jpg",
                "createdAt" => "2020-09-10T15:07:06.190Z",
                "fileId" => "5f5a411a7374d315559daaf0",
                "tags" => null,
                "customCoordinates" => null,
                "isPrivateFile" => false,
                "url" => "https://ik.imagekit.io/test/ineedu.jpg",
                "thumbnail" => "https://ik.imagekit.io/test/tr:n-media_library_thumbnail/ineedu.jpg",
                "fileType" => "image",
                "filePath" => "/ineedu.jpg"
              ],
          "stream" => null
        ];

        $this->assertEquals($expected, $this->adapter->read('something'));
    }

    /** @test */
    public function testCanWrite() {

        // Mock api call
        $this->client->upload(
            Argument::any()
        )->willReturn([
            'err'   => null,
            'success'   => [
                'fileId'    => 'testId',
                'name'      => 'filename.txt',
                "size"      => 5,
                'filePath'  => '/filename.txt',
                'url'       => 'https://ik.imagekit.io/test/filename.txt',
                'fileType'  => 'non-image'
            ],
        ]);

        // What do we  expect?
        $expected = [
            'err'   => null,
            'success'   => [
                'fileId'    => 'testId',
                'name'      => 'filename.txt',
                "size"      => 5,
                'filePath'  => '/filename.txt',
                'url'       => 'https://ik.imagekit.io/test/filename.txt',
                'fileType'  => 'non-image'
            ]
        ];

        $this->assertEquals($expected, $this->adapter->write('path/filename.txt', 'content', new Config()));

    }

    /** @test */
    public function testFileExistsWhenWriting() {

        // Mock api call
        $this->client->upload(Argument::any())->willThrow(new FileExistsException('path/filename.txt'));

        // Exception
        $this->expectException(FileExistsException::class);
        
        $this->adapter->write('path/filename.txt', 'content', new Config());
        
    }

    /** @test */
    public function testCopyAFile() {

        // Find the file
        $retListFiles = '{
            "err": null,
            "success": [{
                "type": "file",
                "name": "test.txt",
                "createdAt": "2020-09-10T15:07:06.190Z",
                "fileId": "5f5a411a7374d315559daaf0",
                "tags": null,
                "customCoordinates": null,
                "isPrivateFile": false,
                "url": "https://ik.imagekit.io/test/oldFileName.txt",
                "fileType": "text",
                "filePath": "/oldFileName.txt"
            }]
        }';
        $this->client->listFiles(Argument::any())->willReturn(json_decode($retListFiles));

        // Upload the file
        $retUpload = '{
            "err": null,
            "success": {
                "fileId": "testId",
                "name": "oldFileName.txt",
                "size": 5,
                "filePath": "/oldFileName.txt",
                "url": "https://ik.imagekit.io/test/oldFileName.txt",
                "fileType": "non-image"
            }
        }';
        $this->client->upload(Argument::any())->willReturn(json_decode($retUpload));


        $this->assertTrue($this->adapter->copy('oldFileName.txt', 'newFileName.txt'));
    }

    /** @test */
    public function testFileNotExists() {

        // Expected result
        $return = '{"err": null, "success": []}';

        // Return "not found file"
        $this->client->listFiles(Argument::any())->willReturn(json_decode($return));

        $this->assertFalse($this->adapter->has('test/test.txt'));
    }

    /** @test */
    public function testFileExists() {

        // Expected result
        $return = '{
            "err": null,
            "success": [{
                "type": "file",
                "name": "test.txt",
                "createdAt": "2020-09-10T15:07:06.190Z",
                "fileId": "5f5a411a7374d315559daaf0",
                "tags": null,
                "customCoordinates": null,
                "isPrivateFile": false,
                "url": "https://ik.imagekit.io/test/oldFileName.txt",
                "fileType": "text",
                "filePath": "/oldFileName.txt"
            }]
        }';

        // Return "not found file"
        $this->client->listFiles(Argument::any())->willReturn(json_decode($return));

        $this->assertTrue($this->adapter->has('test/test.txt'));
    }

     /** @test */
    public function testFileDelete() {

        $retListFiles = '{
            "err": null,
            "success": [{
                "type": "file",
                "name": "test.txt",
                "createdAt": "2020-09-10T15:07:06.190Z",
                "fileId": "5f5a411a7374d315559daaf0",
                "tags": null,
                "customCoordinates": null,
                "isPrivateFile": false,
                "url": "https://ik.imagekit.io/test/test.txt",
                "fileType": "text",
                "filePath": "/test.txt"
            }]
        }';

        $this->client->listFiles(Argument::any())->willReturn(json_decode($retListFiles));

        $this->client->deleteFile(Argument::any())->willReturn(json_decode('{"err": null, "success": null}'));

        $this->assertTrue($this->adapter->delete('test.txt'));
    }

}