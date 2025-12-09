<?php

namespace Devanderson\FilamentMediaGallery\Tests\Database\Factories;

use Devanderson\FilamentMediaGallery\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'path' => 'storage/videos/' . $this->faker->uuid . '.mp4',
            'mime_type' => 'video/mp4',
            'duration' => $this->faker->numberBetween(30, 300),
            'size' => $this->faker->numberBetween(5000000, 50000000),
            'thumbnail_path' => 'storage/thumbnails/' . $this->faker->uuid . '.jpg',
            'width' => $this->faker->numberBetween(1280, 1920),
            'height' => $this->faker->numberBetween(720, 1080),
            'order' => 0,
        ];
    }
}
