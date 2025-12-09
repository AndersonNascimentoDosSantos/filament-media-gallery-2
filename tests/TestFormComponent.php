<?php

namespace Devanderson\FilamentMediaGallery\Tests;

use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Traits\ProcessUploadGallery;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;

class TestFormComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use ProcessUploadGallery;

    public ?array $data = [
        'my_gallery' => null,
        'my_gallery_new_media' => null,
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
                GalleryMediaField::make('my_gallery')
                    ->mediaType('image')
                    ->allowMultiple(true),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return '<div></div>';
    }
}
