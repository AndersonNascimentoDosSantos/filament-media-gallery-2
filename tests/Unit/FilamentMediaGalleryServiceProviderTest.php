<?php

use Devanderson\FilamentMediaGallery\FilamentMediaGalleryServiceProvider;
use Devanderson\FilamentMediaGallery\Tests\TestCase;

it('registers forms components', function () {
    $provider = new FilamentMediaGalleryServiceProvider(app());
    $provider->register();

    expect(config('filament.forms.components'))->not->toBeEmpty();
})->group('service-provider');

