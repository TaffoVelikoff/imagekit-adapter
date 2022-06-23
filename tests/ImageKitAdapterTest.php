<?php

namespace TaffoVelikoff\ImageKitAdapter\Tests;

use ImageKit\ImageKit;
use League\Flysystem\Config;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCreateDirectory;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TaffoVelikoff\ImageKitAdapter\ImagekitAdapter;

class ImageKitAdapterTest extends TestCase {

    use ProphecyTrait;

    protected $client;
    protected $adapter;

    public function setUp(): void {
        parent::setUp();

        $this->client = $this->prophesize(ImageKit::class);
        $this->adapter = new ImagekitAdapter($this->client->reveal());
    }

    private function adapter(): ImagekitAdapter
    {
        return self::createFilesystemAdapter();
    }

    public function metaDataProvider(): array
    {
        return [
            //['visibility'], // Adapter does not support visibility
            ['mimeType'],
            ['lastModified'],
            ['fileSize'],
        ];
    }

    /** @test */
    public function test_can_write() {
        $this->client->upload(Argument::type('array'))->willReturn((object) [
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

        $this->adapter->write('something', 'contents', new Config());
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function test_can_write_stream()
    {
        $this->client->upload(Argument::type('array'))->willReturn((object) [
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

        $this->adapter->writeStream('something', 'contents', new Config());
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     * @dataProvider metaDataProvider
     */
    public function test_can_get_meta_data()
    {
        $this->client = $this->prophesize(ImageKit::class);
        //$this->adapter = $this->prophesize(ImagekitAdapter::class);

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err' => null,
            'success' => [
                0 => (object) ['fileId' => '62b1a4dd1a644d82e12d3455']
            ]
        ]);

        $this->client->getMetadata(Argument::type('string'))->willReturn((object) [
            "type" => "file",
            "name" => "somefile.png",
            "updatedAt" => "2022-06-21T11:00:45.328Z",
            "fileId" => "62b1a4dd1a644d82e12d3455",
            "size" => 2505,
            "mime" => "image/png",
        ]);

        $this->adapter = new ImagekitAdapter($this->client->reveal());

        $this->assertInstanceOf(
            StorageAttributes::class,
            $this->adapter->fileSize('test/test.txt')
        );
    }

    /** @test */
    public function test_can_read()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err' => null,
            'success' => [
                0 => (object) [
                    'url'   => 'https://ik.imagekit.io/qvkco4igg4/taffo/text.txt',
                    'name'  => 'somefile.txt'
                ]
            ]
        ]);

        $this->assertStringContainsString(
            'asd',
            $this->adapter->read('somefile.txt')
        );

    }

    /** @test */
    public function test_can_delete()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err' => null,
            'success' => [
                0 => (object) [
                    'fileId' => '62b1a4dd1a644d82e12d3455'
                ]
            ]
        ]);

        $this->client->deleteFile(Argument::type('string'))->willReturn((object) [
            'err'       => null,
            'success'   => null
        ]);

        $this->client->deleteFolder(Argument::type('string'))->willReturn((object) [
            'err'       => null,
            'success'   => null
        ]);

        $this->adapter->delete('/test/somefile.png');
        $this->addToAssertionCount(1);

        $this->adapter->deleteDirectory('/test/somefile');
        $this->addToAssertionCount(1);

    }

    /** @test */
    public function test_can_not_create_a_directory_without_name()
    {

        $this->client->createFolder(Argument::type('string'))->willReturn((object) [
            'err'       => [
                'message'   => 'Missing data for creation of folder',
                'help'      => ''
            ],
            'succes'    => []
        ]);

        $this->expectException(UnableToCreateDirectory::class);
        $this->adapter->createDirectory('/', new Config());

    }

    /** @test */
    public function test_can_create_a_directory()
    {

        $this->client->createFolder(Argument::type('string'))->willReturn((object) [
            'err'       => [],
            'succes'    => null
        ]);

        $this->adapter->createDirectory('test_directory', new Config());
        $this->addToAssertionCount(1);

    }

    /** @test */
    public function test_can_list_contents()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err'       => null,
            'success'   => [
                0   => (object) [
                    'type'      => 'file',
                    'name'      => 'testfile.png',
                    'createdAt' => '2021-03-02T12:43:51.851Z',
                    'updatedAt' => '2021-03-02T12:43:51.851Z',
                    'fileId'    => '603e33077a5cd779ef851c07',
                    'size'      => 2361,
                    'mime'      => 'image/jpeg',
                    'filePath'  => 'https://ik.imagekit.io/qvkco4igg4/taffo/text.txt'
                ]
            ]
        ]);

        $result = $this->adapter->listContents('', true);
        $this->assertCount(1, $result);

    }

    /** @test */
    public function test_can_move_a_file()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err'       => null,
            'success'   => [
                0   => (object) [
                    'type'      => 'file',
                    'name'      => 'testfile.png',
                    'createdAt' => '2021-03-02T12:43:51.851Z',
                    'updatedAt' => '2021-03-02T12:43:51.851Z',
                    'fileId'    => '603e33077a5cd779ef851c07',
                    'size'      => 2361,
                    'mime'      => 'image/jpeg',
                    'filePath'  => 'https://ik.imagekit.io/qvkco4igg4/taffo/text.txt'
                ]
            ]
        ]);

        $this->client->moveFile(Argument::type('string'), Argument::type('string'))->willReturn((object) [
            'err'       => null,
            'success'   => null
        ]);

        $this->adapter->move('something', 'something', new Config());
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function test_can_move_a_directory()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err'       => null,
            'success'   => [
                0   => (object) [
                    'type'      => 'folder',
                    'name'      => 'some-folder',
                    'createdAt' => '2021-03-02T12:43:51.851Z',
                    'updatedAt' => '2021-03-02T12:43:51.851Z',
                    'fileId'    => '603e33077a5cd779ef851c07',
                ]
            ]
        ]);

        $this->client->moveFolder(Argument::type('string'), Argument::type('string'))->willReturn((object) [
            'err'       => null,
            'success'   => null
        ]);

        $this->adapter->move('some-folder', 'some-other-folder', new Config());
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function test_can_copy_a_file()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err'       => null,
            'success'   => [
                0   => (object) [
                    'type'      => 'file',
                    'name'      => 'testfile.png',
                    'createdAt' => '2021-03-02T12:43:51.851Z',
                    'updatedAt' => '2021-03-02T12:43:51.851Z',
                    'fileId'    => '603e33077a5cd779ef851c07',
                    'size'      => 2361,
                    'mime'      => 'image/jpeg',
                    'filePath'  => 'https://ik.imagekit.io/qvkco4igg4/taffo/text.txt'
                ]
            ]
        ]);

        $this->client->copyFile(Argument::type('string'), Argument::type('string'))->willReturn((object) [
            'err'       => null,
            'success'   => null
        ]);

        $this->adapter->copy('something', 'something', new Config());
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function test_can_copy_a_folder()
    {

        $this->client->listFiles(Argument::type('array'))->willReturn((object) [
            'err'       => null,
            'success'   => [
                0   => (object) [
                    'type'      => 'folder',
                    'name'      => 'some-folder',
                    'createdAt' => '2021-03-02T12:43:51.851Z',
                    'updatedAt' => '2021-03-02T12:43:51.851Z',
                    'fileId'    => '603e33077a5cd779ef851c07',
                    'filePath'  => 'some-folder'
                ]
            ]
        ]);

        $this->client->copyFolder(Argument::type('string'), Argument::type('string'))->willReturn((object) [
            'err'       => null,
            'success'   => null
        ]);

        $this->adapter->copy('something', 'something', new Config());
        $this->addToAssertionCount(1);
    }

    public function test_can_get_client()
    {
        $this->assertInstanceOf(
            ImageKit::class,
            $this->adapter->getClient()
        );
    }


}