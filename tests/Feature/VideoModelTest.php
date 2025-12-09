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
        'nome_original' => 'test.mp4',
        'mime_type' => 'video/mp4',
        'tamanho' => 1234567,
        'duracao' => 12.5,
    ]);

    $this->assertDatabaseHas('videos', [
        'nome_original' => 'test.mp4',
    ]);

    expect($video->url)->toEndWith(Storage::disk('public')->url('galeria/test.mp4'));
    expect($video->thumbnail_url)->toEndWith(Storage::disk('public')->url('thumbnails/test.jpg'));
    expect($video->tamanho_formatado)->toBe('1.18 MB');
    expect($video->duracao_formatada)->toBe('00:12');
});

it('deletes video and thumbnail from storage when deleted', function () {
    $disk = 'public';
    Storage::disk($disk)->put('galeria/video.mp4', 'content');
    Storage::disk($disk)->put('thumbnails/video.jpg', 'content');

    $video = Video::create([
        'path' => 'galeria/video.mp4',
        'thumbnail_path' => 'thumbnails/video.jpg',
        'nome_original' => 'video.mp4',
        'mime_type' => 'video/mp4',
        'tamanho' => 100,
    ]);

    $video->delete();

    Storage::disk($disk)->assertMissing($video->path);
    Storage::disk($disk)->assertMissing($video->thumbnail_path);
});
