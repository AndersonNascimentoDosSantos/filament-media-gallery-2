<?php

use Devanderson\FilamentMediaGallery\Models\Image;
use Illuminate\Support\Facades\Storage;

it('can run stats command', function () {
    Image::factory()->create(['size' => 1024]);

    $this->artisan('filament-media-gallery:stats')
        ->expectsTable(
            ['Métrica', 'Valor'],
            [
                ['Total de Imagens', 1],
                ['Total de Vídeos', 0],
                ['Tamanho Total (Imagens)', '1.00 KB'],
                ['Tamanho Total (Vídeos)', '0 bytes'],
                ['Espaço Total Usado', '1.00 KB'],
            ]
        )
        ->assertSuccessful();
});

it('can run cleanup command', function () {
    Storage::fake('public');
    $path = config('filament-media-gallery.path', 'galeria');

    // Orphan file
    Storage::disk('public')->put($path . '/orphan.jpg', 'content');

    // Non-orphan file
    Storage::disk('public')->put($path . '/real.jpg', 'content');
    Image::factory()->create(['path' => $path . '/real.jpg']);

    $this->artisan('filament-media-gallery:cleanup')
        ->expectsConfirmation('Deseja continuar com a limpeza?', 'yes')
        ->expectsOutputToContain('1 arquivo(s) órfão(s) de imagem deletado(s)')
        ->assertSuccessful();

    Storage::disk('public')->assertMissing($path . '/orphan.jpg');
    Storage::disk('public')->assertExists($path . '/real.jpg');
});

it('can run cleanup command with dry-run', function () {
    Storage::fake('public');
    $path = config('filament-media-gallery.path', 'galeria');
    Storage::disk('public')->put($path . '/orphan.jpg', 'content');

    $this->artisan('filament-media-gallery:cleanup', ['--dry-run' => true])
        ->expectsOutputToContain('Modo DRY RUN')
        ->assertSuccessful();

    Storage::disk('public')->assertExists($path . '/orphan.jpg');
});
