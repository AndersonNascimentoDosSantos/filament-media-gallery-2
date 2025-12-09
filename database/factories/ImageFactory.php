<?php

namespace Devanderson\FilamentMediaGallery\Database\Factories;

use Devanderson\FilamentMediaGallery\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        $fileName = $this->faker->word() . '.jpg';

        return [
            'path' => config('filament-media-gallery.path', 'galeria') . '/' . $fileName,
            'original_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10000, 500000),
            'alt' => $this->faker->sentence(),
        ];
    }
}
