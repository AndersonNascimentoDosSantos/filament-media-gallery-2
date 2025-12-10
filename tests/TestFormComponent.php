<?php

namespace Devanderson\FilamentMediaGallery\Tests;
use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Traits\ProcessaVideoThumbnail;
use Devanderson\FilamentMediaGallery\Traits\ProcessUploadGallery;
use Devanderson\FilamentMediaGallery\Models\Video;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;

class TestFormComponent extends Component implements HasForms
{
    use InteractsWithForms, ProcessUploadGallery,ProcessaVideoThumbnail;


    public ?array $data = [
        'images_ids' => null,
        'images_ids_new_media' => null,
        'videos_ids' => null,
        'videos_ids_new_media' => null,
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                GalleryMediaField::make('images_ids')
                    ->mediaType('image')
                    ->allowMultiple(true),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return '<div></div>';
    }

    /**
     * Simulates regenerating a thumbnail for an existing video record.
     * This is useful for testing edit/update scenarios.
     *
     * @param int $videoId
     */
    public function regenerateThumbnail(int $videoId): void
    {
        $videoRecord = Video::find($videoId);

        if ($videoRecord && $videoRecord->path) {
            \Log::info('TestFormComponent - Regenerating thumbnail for video:', [
                'videoId' => $videoId,
                'videoPath' => $videoRecord->path,
            ]);

            $newThumbnailPath = $this->gerarThumbnailVideo($videoRecord->path);

            if ($newThumbnailPath) {
                $videoRecord->thumbnail_path = $newThumbnailPath;
                $videoRecord->save();
            }
        }
    }
}
