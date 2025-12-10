<?php

namespace Devanderson\FilamentMediaGallery\Database\Factories;

use Devanderson\FilamentMediaGallery\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        $fileName = $this->faker->word() . '.mp4';
        $thumbnailName = 'thumb_' . $this->faker->word() . '.jpg';

        return [
            'path' => config('filament-media-gallery.path', 'galeria') . '/' . $fileName,
            'thumbnail_path' => 'thumbnails/' . $thumbnailName,
            'original_name' => $fileName,
            'mime_type' => 'video/mp4',
            'size' => $this->faker->numberBetween(1000000, 50000000),
            'duration' => $this->faker->randomFloat(2, 10, 300),
        ];
    }
}
