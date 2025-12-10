<?php

use Devanderson\FilamentMediaGallery\Facades\FilamentMediaGallery;
use Devanderson\FilamentMediaGallery\Models\Image;
use Devanderson\FilamentMediaGallery\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->disk = 'public';
    $this->path = 'galeria';
    config(['filament-media-gallery.disk' => $this->disk]);
    config(['filament-media-gallery.path' => $this->path]);
});

it('returns correct stats', function () {
    Image::create(['path' => '1.jpg', 'original_name' => '1.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024]);
    Image::create(['path' => '2.jpg', 'original_name' => '2.jpg', 'mime_type' => 'image/jpeg', 'size' => 2048]);
    Video::create(['path' => '1.mp4', 'original_name' => '1.mp4', 'mime_type' => 'video/mp4', 'size' => 4096]);

    $stats = FilamentMediaGallery::getStats();

    expect($stats['total_imagens'])->toBe(2);
    expect($stats['total_videos'])->toBe(1);
    expect($stats['tamanho_total_imagens'])->toBe('3.00 KB');
    expect($stats['tamanho_total_videos'])->toBe('4.00 KB');
    expect($stats['espaco_total_usado'])->toBe('7.00 KB');
});

it('paginates images and videos', function () {
    Image::factory()->count(30)->create();
    Video::factory()->count(30)->create();

    $images = FilamentMediaGallery::getImages();
    expect($images)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    expect($images->count())->toBe(24); // Default per_page

    $videos = FilamentMediaGallery::getVideos(10);
    expect($videos)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    expect($videos->count())->toBe(10);
});

it('can find media by id', function () {
    $image = Image::factory()->create();
    $video = Video::factory()->create();

    expect(FilamentMediaGallery::getImage($image->id)->id)->toBe($image->id);
    expect(FilamentMediaGallery::getVideo($video->id)->id)->toBe($video->id);
});

it('can delete media by id', function () {
    $image = Image::factory()->create();
    $video = Video::factory()->create();

    FilamentMediaGallery::deleteImage($image->id);
    FilamentMediaGallery::deleteVideo($video->id);

    $this->assertSoftDeleted('images', ['id' => $image->id]);
    $this->assertDatabaseMissing('videos', ['id' => $video->id]);
});

it('cleans orphan images and videos', function () {
    // Arquivo registrado
    Storage::disk($this->disk)->put($this->path . '/registered.jpg', 'content');
    Image::create(['path' => $this->path . '/registered.jpg', 'original_name' => 'registered.jpg', 'mime_type' => 'image/jpeg', 'size' => 100]);

    // Arquivo órfão
    Storage::disk($this->disk)->put($this->path . '/orphan.jpg', 'content');

    // Arquivo de vídeo órfão
    Storage::disk($this->disk)->put($this->path . '/orphan_video.mp4', 'content');

    Storage::disk($this->disk)->assertExists($this->path . '/orphan.jpg');
    Storage::disk($this->disk)->assertExists($this->path . '/orphan_video.mp4');

    $deleted = FilamentMediaGallery::cleanOrphans();

    expect($deleted['images'])->toContain($this->path . '/orphan.jpg');
    expect($deleted['videos'])->toContain($this->path . '/orphan_video.mp4');

    Storage::disk($this->disk)->assertMissing($this->path . '/orphan.jpg');
    Storage::disk($this->disk)->assertMissing($this->path . '/orphan_video.mp4');
    Storage::disk($this->disk)->assertExists($this->path . '/registered.jpg');
});
