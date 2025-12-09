<?php

use Devanderson\FilamentMediaGallery\Tests\TestCase;
use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Tests\Database\Factories\ImageFactory;
use Devanderson\FilamentMediaGallery\Tests\Database\Factories\VideoFactory;
use Filament\Schemas\Components\Livewire;

it('renders gallery field correctly', function () {
    $component = GalleryMediaField::make('media');

    Livewire::test($component->getLivewireName())
        ->assertSee('Upload Image')
        ->assertSee('Upload Video');
})->group('gallery-media-field');

it('displays images in gallery', function () {
    $images = ImageFactory::count(3)->create();

    Livewire::test(GalleryMediaField::make('media'))
        ->assertSee($images->first()->name);
})->group('gallery-media-field');

it('displays videos with thumbnails', function () {
    $videos = VideoFactory::count(2)->create();

    Livewire::test(GalleryMediaField::make('media'))
        ->assertSee($videos->first()->thumbnail_path);
})->group('gallery-media-field');

it('handles multiple selections', function () {
    ImageFactory::count(5)->create();

    Livewire::test(GalleryMediaField::make('media'))
        ->call('selectMedia', 1)
        ->call('selectMedia', 3)
        ->assertSet('selectedMedia', [1, 3]);
})->group('gallery-media-field');
