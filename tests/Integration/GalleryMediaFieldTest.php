<?php

namespace Devanderson\FilamentMediaGallery\Tests\Integration;

use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Models\Image;
use Devanderson\FilamentMediaGallery\Models\Video;
use Devanderson\FilamentMediaGallery\Tests\TestFormComponent;
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
        ->assertSee('images_ids');
})->group('gallery-media-field');

it('displays images in gallery', function () {
    $images = Image::factory()->count(3)->create();

    livewire(TestFormComponent::class)
        ->set('data.images_ids', $images->pluck('id')->toArray())
        ->assertSet('data.images_ids', $images->pluck('id')->toArray());
})->group('gallery-media-field');

it('displays videos with thumbnails', function () {
    $videos = Video::factory()->count(2)->create();

    // Create a custom component that accepts videos
    $component = new class extends TestFormComponent {
        public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
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

    livewire($component::class)
        ->set('data.videos_ids', $videos->pluck('id')->toArray())
        ->assertSet('data.videos_ids', $videos->pluck('id')->toArray());
})->group('gallery-media-field');

it('handles multiple selections', function () {
    $images = Image::factory()->count(5)->create();

    livewire(TestFormComponent::class)
        ->set('data.images_ids', [$images[0]->id, $images[2]->id])
        ->assertSet('data.images_ids', [$images[0]->id, $images[2]->id]);
})->group('gallery-media-field');
