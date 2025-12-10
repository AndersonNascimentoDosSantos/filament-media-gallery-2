<?php

use Devanderson\FilamentMediaGallery\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('can create an image', function () {
    $image = Image::create([
        'path' => 'galeria/test.jpg',
        'original_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
        'alt' => 'Test Image',
    ]);

    $this->assertDatabaseHas('images', [
        'original_name' => 'test.jpg',
        'alt' => 'Test Image',
    ]);

    expect($image->url)->toEndWith(Storage::disk('public')->url('galeria/test.jpg'));
    expect($image->size_formatted)->toBe('12.06 KB');
});

it('deletes file from storage when force deleted', function () {
    $disk = 'public';
    Storage::disk($disk)->put('galeria/image.jpg', 'content');

    $image = Image::create([
        'path' => 'galeria/image.jpg',
        'original_name' => 'image.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 100,
    ]);

    $this->assertDatabaseHas('images', ['id' => $image->id]);
    Storage::disk($disk)->assertExists($image->path);

    $image->delete(); // Soft delete
    $this->assertSoftDeleted($image);
    Storage::disk($disk)->assertExists($image->path);

    $image->forceDelete(); // Force delete
    $this->assertDatabaseMissing('images', ['id' => $image->id]);
    Storage::disk($disk)->assertMissing($image->path);
});
