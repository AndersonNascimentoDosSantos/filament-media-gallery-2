<?php

use Devanderson\FilamentMediaGallery\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('can create a video', function () {
    $video = Video::create([
        'path' => 'galeria/test.mp4',
        'thumbnail_path' => 'thumbnails/test.jpg',
        'original_name' => 'test.mp4',
        'mime_type' => 'video/mp4',
        'size' => 1.18*1024*1024,
        'duration' => 12.5,
    ]);

    $this->assertDatabaseHas('videos', [
        'original_name' => 'test.mp4',
    ]);
//dd($video->duration_formatted);
    expect($video->url)->toEndWith(Storage::disk('public')->url('galeria/test.mp4'));
    expect($video->thumbnail_url)->toEndWith(Storage::disk('public')->url('thumbnails/test.jpg'));
    expect($video->size_formatted)->toBe('1.18 MB');
    expect($video->duration_formatted)->toBe('00:12');
});

it('deletes video and thumbnail from storage when deleted', function () {
    $disk = 'public';
    Storage::disk($disk)->put('galeria/video.mp4', 'content');
    Storage::disk($disk)->put('thumbnails/video.jpg', 'content');

    $video = Video::create([
        'path' => 'galeria/video.mp4',
        'thumbnail_path' => 'thumbnails/video.jpg',
        'original_name' => 'video.mp4',
        'mime_type' => 'video/mp4',
        'size' => 100   ]);

    $video->delete();

    Storage::disk($disk)->assertMissing($video->path);
    Storage::disk($disk)->assertMissing($video->thumbnail_path);
});
