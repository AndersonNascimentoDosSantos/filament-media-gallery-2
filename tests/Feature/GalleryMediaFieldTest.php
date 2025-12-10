<?php

namespace Devanderson\FilamentMediaGallery\Tests\Feature;

use Devanderson\FilamentMediaGallery\FilamentMediaGallery;
use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;
use Devanderson\FilamentMediaGallery\Models\Image;
use Devanderson\FilamentMediaGallery\Models\Video;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Devanderson\FilamentMediaGallery\Tests\TestFormComponent;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Storage::fake('public');
    config(['filesystems.disks.public.url' => '/storage']);
});

it('can render the gallery field', function () {
    livewire(TestFormComponent::class)
        ->assertFormFieldExists('images_ids');
});

it('can upload a new image', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');

    livewire(TestFormComponent::class)
        ->set('data.images_ids_new_media', $file)
        ->call('handleNewMediaUpload', $file->getFilename(), 'data.images_ids')
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

    livewire(TestFormComponent::class)
        ->set('data.images_ids', [$image->id])
        ->assertSet('data.images_ids', [$image->id]);
});

it('can load more media', function () {
    Image::factory()->count(30)->create();

    Livewire::test(TestFormComponent::class)
        ->call('loadMoreMedias', 2, 'data.images_ids')
        ->assertReturned(function (array $response) {
            return count($response['medias']) === 6 && $response['hasMore'] === false;
        });
});

it('can update media alt text', function () {
    $image = Image::factory()->create(['alt' => 'Old Alt']);

    livewire(TestFormComponent::class)
        ->call('updateMediaAlt', $image->id, 'New Alt Text', 'data.images_ids');

    $image->refresh();
    expect($image->alt)->toBe('New Alt Text');
});

it('handles single media upload limit', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $image = Image::factory()->create();

    // Custom component for single upload
    $component = new class extends TestFormComponent {
        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    GalleryMediaField::make('images_ids')
                        ->mediaType('image')
                        ->allowMultiple(false),
                ])
                ->statePath('data');
        }
    };

    // Inicializa o componente com o estado pré-existente.
    // Isso é crucial para que o `getForm()` possa acessar a configuração correta.
    $livewire = livewire($component::class, ['data' => ['images_ids' => [$image->id]]])
        // Agora, simula o upload de um novo arquivo.
        ->set('data.images_ids_new_media', $file);

    // Get the component instance from the form and extract its config
    $form = $livewire->instance()->getForm('form');
    $field = $form->getComponent('images_ids');
    $fieldConfig = $field->getComponentConfig();

    $livewire
        ->call('handleNewMediaUpload', $file->getFilename(), 'data.images_ids', $fieldConfig)
        ->assertNotified(
            Notification::make()
                ->warning()
                ->title(__('filament-media-gallery::filament-media-gallery.notifications.limit_reached.title'))
                ->body(__('filament-media-gallery::filament-media-gallery.notifications.limit_reached.single'))
        );

    // Verify no new image was created (should still be only 1)
    $this->assertDatabaseCount('images', 1);
});


it('can upload a video and generate a thumbnail', function () {
    // This test assumes FFmpeg is installed in the test environment (like in the Dockerfile)
    $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

    // Create a custom component for video uploads
    $videoComponent = new class extends TestFormComponent {

        public function form(Schema $schema):Schema
        {
            return $schema->components([
                GalleryMediaField::make('videos_ids')
                    ->mediaType('video')
                    ->allowMultiple(true),
            ])->statePath('data');
        }
    };

    $livewire = livewire($videoComponent::class)
        ->set('data.videos_ids_new_media', $file);

    // Get the component instance from the form and extract its config
    $form = $livewire->instance()->getForm('form');
    $field = $form->getComponent('videos_ids');
    $fieldConfig = $field->getComponentConfig();

    $livewire->call('handleNewMediaUpload', $file->getFilename(), 'data.videos_ids', $fieldConfig)
        ->assertHasNoErrors()
        ->assertDispatched('galeria:media-added');

    $this->assertDatabaseCount('videos', 1);

    $video = Video::first();
//    dd($video);
//    expect($video->original_name)->toBe('video.mp4')
//        ->and($video->thumbnail_path)->not->toBeNull();

    Storage::disk('public')->assertExists($video->path);
    Storage::disk('public')->assertExists($video->thumbnail_path);
})->skip(! (new FilamentMediaGallery)->hasFFmpeg(), 'FFmpeg is not available.');
