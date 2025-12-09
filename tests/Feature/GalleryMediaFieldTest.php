<?php

namespace Devanderson\FilamentMediaGallery\Tests\Feature;

use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Models\Image;
use Devanderson\FilamentMediaGallery\Traits\ProcessUploadGallery;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Form;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

class GalleryMediaFieldTest extends Component implements HasForms
{
    use InteractsWithForms;
    use ProcessUploadGallery;

    public $data = [
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

beforeEach(function () {
    Storage::fake('public');
    config(['filesystems.disks.public.url' => '/storage']);
});

it('can render the gallery field', function () {
    livewire(GalleryMediaFieldTest::class)
        ->assertFormFieldExists('my_gallery');
});

it('can upload a new image', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');

    livewire(GalleryMediaFieldTest::class)
        ->set('data.my_gallery_new_media', $file)
        ->call('handleNewMediaUpload', $file->getFilename(), 'data.my_gallery')
        ->assertHasNoErrors()
        ->assertDispatched('galeria:media-added')
        ->assertNotified(
            Notification::make()
                ->success()
                ->title(__('filament-media-gallery::filament-media-gallery.notifications.upload_complete.title'))
                ->body(__('filament-media-gallery::filament-media-gallery.notifications.upload_complete.body'))
        );

    $this->assertDatabaseCount('images', 1);
    $image = Image::first();
    Storage::disk('public')->assertExists($image->path);
});

it('can select an existing image', function () {
    $image = Image::factory()->create();

    livewire(GalleryMediaFieldTest::class)
        ->set('data.my_gallery', [$image->id])
        ->assertSet('data.my_gallery', [$image->id]);
});

it('can load more media', function () {
    Image::factory()->count(30)->create();

    Livewire::test(GalleryMediaFieldTest::class)
        ->call('loadMoreMedias', 2, 'data.my_gallery')
        ->assertReturned(function (array $response) {
            return count($response['medias']) === 6 && $response['hasMore'] === true;
        });
});

it('can update media alt text', function () {
    $image = Image::factory()->create(['alt' => 'Old Alt']);

    livewire(GalleryMediaFieldTest::class)
        ->call('updateMediaAlt', $image->id, 'New Alt Text', 'data.my_gallery');

    $image->refresh();
    expect($image->alt)->toBe('New Alt Text');
});

it('handles single media upload limit', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $image = Image::factory()->create();

    // Custom component for single upload
    $livewire = livewire(new class extends GalleryMediaFieldTest {
        public function form(\Filament\Schemas\Components\Form $form): Form
        {
            return $form
                ->schema([
                    GalleryMediaField::make('my_gallery')
                        ->mediaType('image')
                        ->allowMultiple(false),
                ])
                ->statePath('data');
        }
    });

    $livewire->set('data.my_gallery', [$image->id]) // Already has an image
    ->set('data.my_gallery_new_media', $file)
        ->call('handleNewMediaUpload', $file->getFilename(), 'data.my_gallery')
        ->assertNotified();

    $this->assertDatabaseCount('images', 1); // No new image should be created
});
