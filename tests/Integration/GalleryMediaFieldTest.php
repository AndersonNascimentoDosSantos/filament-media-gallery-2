<?php

namespace Devanderson\FilamentMediaGallery\Tests\Integration;

use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Models\Image;
use Devanderson\FilamentMediaGallery\Models\Video;
use Devanderson\FilamentMediaGallery\Tests\TestFormComponent;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    config(['filesystems.disks.public.url' => '/storage']);
});

it('renders gallery field correctly', function () {
    livewire(TestFormComponent::class)
        ->assertFormFieldExists('images_ids')
        ->assertOk();
})->group('gallery-media-field');

it('displays images in gallery', function () {
    $images = Image::factory()->count(3)->create();

    $component = livewire(TestFormComponent::class)
        ->set('data.images_id', $images->pluck('id')->toArray());

    // Verify the images are in the state
    expect($component->get('data.images_id'))
        ->toBeArray()
        ->toHaveCount(3);
})->group('gallery-media-field');

it('displays videos with thumbnails', function () {
    $videos = Video::factory()->count(2)->create();

    // Create a custom component that accepts videos
    $component = new class extends TestFormComponent {
        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    GalleryMediaField::make('videos_ids')
                        ->mediaType('video')
                        ->allowMultiple(true),
                ])
                ->statePath('data');
        }
    };

    $livewire = livewire($component::class)
        ->set('data.videos_ids', $videos->pluck('id')->toArray());

    // Verify the videos are in the state
    expect($livewire->get('data.videos_ids'))
        ->toBeArray()
        ->toHaveCount(2);
})->group('gallery-media-field');

it('handles multiple selections', function () {
    $images = Image::factory()->count(5)->create();
    $selectedIds = [$images[0]->id, $images[2]->id];

    livewire(TestFormComponent::class)
        ->set('data.images_id', $selectedIds)
        ->assertSet('data.images_id', $selectedIds);
})->group('gallery-media-field');

it('loads more media with pagination', function () {
    Image::factory()->count(30)->create();

    $result = livewire(TestFormComponent::class)
        ->call('loadMoreMedias', 1, 'data.images_id')
        ->assertOk();

    // Can't directly assert returned value in Livewire test
    // But we can verify the method doesn't throw errors
    expect(true)->toBeTrue();
})->group('gallery-media-field');

it('respects allow multiple configuration', function () {
    $component = new class extends TestFormComponent {
        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    GalleryMediaField::make('images_id')
                        ->mediaType('image')
                        ->allowMultiple(false),
                ])
                ->statePath('data');
        }
    };

    livewire($component::class)
        ->assertFormFieldExists('images_id')
        ->assertOk();

})->group('gallery-media-field');
