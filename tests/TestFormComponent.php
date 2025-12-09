<?php

namespace Devanderson\FilamentMediaGallery\Tests;

use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Traits\ProcessUploadGallery;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Form;
use Livewire\Component;

class TestFormComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use ProcessUploadGallery;

    public ?array $data = [
        'my_gallery' => null,
        'my_gallery_new_media' => null,
    ];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
