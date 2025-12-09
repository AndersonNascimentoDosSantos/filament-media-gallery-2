<?php

use Devanderson\FilamentMediaGallery\FilamentMediaGallery;
use Devanderson\FilamentMediaGallery\FilamentMediaGalleryServiceProvider;
use Devanderson\FilamentMediaGallery\Tests\TestCase;

it('registers forms components', function () {
    $provider = new FilamentMediaGalleryServiceProvider(app());
    $provider->register();
    $provider->boot();

    // Verify the service provider is registered
    expect(app()->getProvider(FilamentMediaGalleryServiceProvider::class))
        ->not->toBeNull()
        ->and(FilamentMediaGallery::class)->toBeClass();

    // Or verify the facade is working
})->group('service-provider');

