<?php

namespace Spatie\MediaLibrary\Test\FileAdder;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Test\TestCase;

class S3IntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        if (! $this->canTestS3) {
            $this->markTestSkipped('Skipping S3 tests because no S3 env variables found');
        }
    }

    public function tearDown()
    {
        $this->cleanUpS3();

        parent::tearDown();
    }

    /** @test */
    public function it_store_a_file_on_s3()
    {
        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaLibrary('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$media->id}/test.jpg"));
    }

    /** @test */
    public function it_store_a_file_and_its_conversion_on_s3()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaLibrary('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$media->id}/test.jpg"));
        $this->assertTrue(Storage::disk('s3')->has("{$media->id}/conversions/thumb.jpg"));
    }

    /** @test */
    public function it_delete_a_file_on_s3()
    {
        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaLibrary('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$media->id}/test.jpg"));

        $media->delete();

        $this->assertFalse(Storage::disk('s3')->has("{$media->id}/test.jpg"));
    }

    /** @test */
    public function it_delete_a_file_converions_on_s3()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaLibrary('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$media->id}/test.jpg"));
        $this->assertTrue(Storage::disk('s3')->has("{$media->id}/conversions/thumb.jpg"));

        $media->delete();

        $this->assertFalse(Storage::disk('s3')->has("{$media->id}/test.jpg"));
        $this->assertFalse(Storage::disk('s3')->has("{$media->id}/conversions/thumb.jpg"));
    }

    /** @test */
    public function it_retrieve_a_media_url_from_s3()
    {
        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->preservingOriginal()
            ->toMediaLibrary('default', 's3');

        $this->assertEquals(
            $this->app['config']->get('laravel-medialibrary.s3.domain')."/{$media->id}/test.jpg",
            $media->getUrl()
        );

        // Need to allow s3 read from travis
        $this->assertEquals(
            sha1(file_get_contents($this->getTestJpg())),
            sha1(file_get_contents($media->getUrl()))
        );
    }

    /** @test */
    public function it_retrieve_a_media_conversion_url_from_s3()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaLibrary('default', 's3');

        $this->assertEquals(
            $this->app['config']->get('laravel-medialibrary.s3.domain')."/{$media->id}/conversions/thumb.jpg",
            $media->getUrl('thumb')
        );
    }

    protected function cleanUpS3()
    {
        collect(Storage::disk('s3')->allDirectories())->each(function ($directory) {
            Storage::disk('s3')->deleteDirectory($directory);
        });
    }
}
