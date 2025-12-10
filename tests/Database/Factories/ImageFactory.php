<?php

namespace Devanderson\FilamentMediaGallery\Tests\Database\Factories;

use Devanderson\FilamentMediaGallery\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->imageUrl(800, 600),
            'path' => 'storage/images/' . $this->faker->uuid . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10000, 500000),
            'width' => $this->faker->numberBetween(800, 1920),
            'height' => $this->faker->numberBetween(600, 1080),
            'thumbnail_path' => 'storage/thumbnails/' . $this->faker->uuid . '.jpg',
            'order' => 0,
            'is_featured' => $this->faker->boolean(20),
        ];
    }

    public function video(): self
    {
        return $this->state(fn () => [
            'mime_type' => 'video/mp4',
            'path' => 'storage/videos/' . $this->faker->uuid . '.mp4',
        ]);
    }
}
