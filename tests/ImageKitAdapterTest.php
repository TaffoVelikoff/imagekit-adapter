<?php

namespace TaffoVelikoff\ImageKitAdapter\Tests;

use TaffoVelikoff\ImageKitAdapter\ImagekitAdapter;

class ImageKitAdapterTest extends \Orchestra\Testbench\TestCase {

    protected $client;
    protected $adapter;

    public function setUp(): void {
        parent::setUp();
    }

    protected function getPackageProviders($app) {
        return [\TaffoVelikoff\ImageKitAdapter\ImageKitServiceProvider::class];
    }

    public function testIsTrue() {
        $this->assertTrue(true);
    }

}