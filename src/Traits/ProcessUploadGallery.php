<?php

namespace Devanderson\FilamentMediaGallery\Traits;

use Devanderson\FilamentMediaGallery\Models\Video;
use Devanderson\FilamentMediaGallery\Models\Image;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ProcessUploadGallery
{
    /**
     * Field configuration cache to avoid repeated lookups
     */
    protected array $fieldConfigCache = [];

    /**
     * Gets the configuration of a media field.
     */
    protected function getFieldConfig(string $statePath): ?array
    {
        // Remove 'data.' prefix if exists
        $key = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;

        // Check if already in cache
        if (isset($this->fieldConfigCache[$key])) {
            return $this->fieldConfigCache[$key];
        }

        \Log::info('ProcessaUploadGaleria: Fetching field configuration', [
            'statePath' => $statePath,
            'key' => $key
        ]);

        // Try to access form if exists
        if (property_exists($this, 'form') && method_exists($this, 'form')) {
            try {
                // This is a more robust way to get the component definition, especially in tests.
                $form = $this->getForm(config('forms.default_form_name', 'form'));
                $components = $form->getSchema()->getComponents(true);

                foreach ($components as $component) {
                    if ($component->getName() === $key && method_exists($component, 'getMediaType')) {
                        $config = [
                            'mediaType' => $component->getMediaType(),
                            'modelClass' => $component->getModelClass(),
                            'allowMultiple' => $component->getAllowMultiple(),
                            'allowUpload' => $component->getAllowUpload(),
                            'maxItems' => $component->getMaxItems(),
                        ];
                        $this->fieldConfigCache[$key] = $config;
                        \Log::info('ProcessaUploadGaleria: Configuration obtained from component', $config);
                        return $config;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('ProcessaUploadGaleria: Error accessing form', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Fallback: infer from field name
        $config = $this->inferFieldConfig($key);

        if ($config) {
            $this->fieldConfigCache[$key] = $config;
            return $config;
        }

        return null;
    }

    /**
     * Infers field configuration based on name
     */
    protected function inferFieldConfig(string $fieldName): ?array
    {
        \Log::info('ProcessaUploadGaleria: Inferring configuration', [
            'fieldName' => $fieldName
        ]);

        // Detect if it's a video or image field by name
        $isVideoField = str_contains(strtolower($fieldName), 'video');

        // Use models from config
        $imageModel = config('filament-media-gallery.image.model', Image::class);
        $videoModel = config('filament-media-gallery.video.model', Video::class);

        $config = [
            'mediaType' => $isVideoField ? 'video' : 'image',
            'modelClass' => $isVideoField ? $videoModel : $imageModel,
            'allowMultiple' => config('filament-media-gallery.gallery.allow_multiple', true),
            'allowUpload' => true,
            'maxItems' => config('filament-media-gallery.gallery.max_items', null),
        ];

        \Log::info('ProcessaUploadGaleria: Inferred configuration', $config);

        return $config;
    }

    /**
     * Processes the upload of new media (image or video).
     */
    public function handleNewMediaUpload(string $uploadedFilename, string $statePath, ?array $fieldConfig = null): void
    {
        try {
            \Log::info('ProcessaUploadGaleria: Starting handleNewMediaUpload', [
                'uploadedFilename' => $uploadedFilename,
                'statePath' => $statePath,
                'hasFieldConfig' => !is_null($fieldConfig)
            ]);

            // Use the provided config or fetch it if not available
            $config = $fieldConfig ?? $this->getFieldConfig($statePath);

            if (!$config) {
                \Log::error("ProcessaUploadGaleria: Configuration for field '$statePath' not found.");
                throw new \Exception("Unable to get configuration for field '$statePath'.");
            }

            $allowMultiple = $config['allowMultiple'];
            $mediaType = $config['mediaType'];
            $modelClass = $config['modelClass'];

            \Log::info('ProcessaUploadGaleria: Field configurations', [
                'mediaType' => $mediaType,
                'modelClass' => $modelClass,
                'allowMultiple' => $allowMultiple
            ]);

            // Remove 'data.' prefix to access $this->data array
            $dataKey = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;

            if (!$allowMultiple) {
                $currentState = $this->data[$dataKey] ?? [];

                if (is_string($currentState)) {
                    $currentState = json_decode($currentState, true) ?? [];
                }

                if (!empty($currentState)) {
                    Notification::make()
                        ->warning()
                        ->title(__('filament-media-gallery::filament-media-gallery.notifications.limit_reached.title'))
                        ->body(__('filament-media-gallery::filament-media-gallery.notifications.limit_reached.single'))
                        ->send();
                    return;
                }
            }

            $uploadKey = $dataKey . '_new_media';

            // Try to get file in multiple ways (Livewire compatibility)
            $tempFile = $this->data[$uploadKey] ?? $this->{$uploadKey} ?? null;

            \Log::info('ProcessaUploadGaleria: Checking temporary file', [
                'uploadKey' => $uploadKey,
                'tempFile_exists' => $tempFile !== null,
                'tempFile_class' => $tempFile ? get_class($tempFile) : 'null',
                'data_keys' => array_keys($this->data ?? []),
                'uploadedFilename' => $uploadedFilename
            ]);

            // If not found by uploadKey, try to fetch file directly by upload name
            if (!$tempFile && property_exists($this, $uploadKey)) {
                $tempFile = $this->{$uploadKey};
            }

            if (!$tempFile instanceof TemporaryUploadedFile) {
                \Log::error('ProcessaUploadGaleria: Invalid temporary file', [
                    'tempFile_type' => gettype($tempFile),
                    'tempFile_value' => $tempFile,
                    'available_properties' => get_object_vars($this)
                ]);
                throw new \Exception('Temporary file not found or invalid.');
            }

            // Use plugin configurations
            $disk = config('filament-media-gallery.disk', 'public');
            $path = config('filament-media-gallery.path', 'galeria');

            $newPath = $tempFile->store($path, $disk);

            \Log::info('ProcessaUploadGaleria: File stored', [
                'newPath' => $newPath,
                'original_name' => $tempFile->getClientOriginalName(),
                'disk' => $disk
            ]);

            $dataToCreate = [
                'path' => $newPath,
                'original_name' => $tempFile->getClientOriginalName(),
                'mime_type' => $tempFile->getMimeType(),
                'size' => $tempFile->getSize(),
            ];

            // Add 'alt' field only for images
            if ($mediaType === 'image') {
                $dataToCreate['alt'] = $tempFile->getClientOriginalName();
            }

            $media = $modelClass::create($dataToCreate);

            // Generate thumbnail for videos if enabled
            if ($mediaType === 'video' && config('filament-media-gallery.video.thumbnail.enabled', true)) {
                // Get the full path for FFmpeg, which is crucial for Storage::fake() in tests
                $fullPath = Storage::disk($disk)->path($newPath);
                \Log::info('ProcessaUploadGaleria: Generating video thumbnail', ['fullPath' => $fullPath]);

                $thumbnail = $this->gerarThumbnailVideo($fullPath);
                if ($thumbnail) {
                    $media->update(['thumbnail_path' => $thumbnail]);
                }
            }

            \Log::info('ProcessaUploadGaleria: Media created', [
                'media_id' => $media->id,
                'model_class' => $modelClass
            ]);

            $currentState = $this->data[$dataKey] ?? [];
            if (is_string($currentState)) {
                $currentState = json_decode($currentState, true) ?? [];
            }
            $currentState[] = $media->id;
            $this->data[$dataKey] = $currentState;

            $this->data[$uploadKey] = null;

            Notification::make()
                ->success()
                ->title(__('filament-media-gallery::filament-media-gallery.notifications.upload_complete.title'))
                ->body(__('filament-media-gallery::filament-media-gallery.notifications.upload_complete.body'))
                ->send();

            // Prepare data for dispatch
            $mediaData = [
                'id' => $media->id,
                'url' => $media->url,
                'original_name' => $media->original_name,
                'is_video' => $mediaType === 'video',
            ];

            // Add thumbnail for videos
            if ($mediaType === 'video' && method_exists($media, 'getThumbnailUrlAttribute')) {
                $mediaData['thumbnail_url'] = $media->thumbnail_url;
            }

            // Add alt for images
            if ($mediaType === 'image' && isset($media->alt)) {
                $mediaData['alt'] = $media->alt;
            }

            // DISPATCH EVENT TO UPDATE UI
            $this->dispatch('galeria:media-added', media: $mediaData);

            \Log::info('ProcessaUploadGaleria: Upload completed successfully', [
                'media_id' => $media->id
            ]);

        } catch (\Exception $e) {
            \Log::error('ProcessaUploadGaleria: Error in handleNewMediaUpload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title(__('filament-media-gallery::filament-media-gallery.notifications.upload_error.title'))
                ->body($e->getMessage())
                ->send();

            throw $e;
        }
    }

    /**
     * Processes the upload of an edited image.
     */
    public function handleEditedMediaUpload($mediaId, $fileName, $statePath): void
    {
        try {
            \Log::info('ProcessaUploadGaleria: Starting handleEditedMediaUpload', [
                'mediaId' => $mediaId,
                'fileName' => $fileName,
                'statePath' => $statePath
            ]);

            // Remove 'data.' prefix if exists
            $dataKey = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;
            $uploadKey = $dataKey . '_edited_media';

            // Try to get file in multiple ways (Livewire compatibility)
            $tempFile = $this->data[$uploadKey] ?? $this->{$uploadKey} ?? null;

            // If not found by uploadKey, try to fetch file directly
            if (!$tempFile && property_exists($this, $uploadKey)) {
                $tempFile = $this->{$uploadKey};
            }

            if (!$tempFile instanceof TemporaryUploadedFile) {
                \Log::error('ProcessaUploadGaleria: Edited file not found', [
                    'uploadKey' => $uploadKey,
                    'tempFile_type' => gettype($tempFile)
                ]);
                throw new \Exception('Edited file not found.');
            }

            // Use model from config
            $imageModel = config('filament-media-gallery.image.model', Image::class);
            $image = $imageModel::find($mediaId);

            if (!$image) {
                throw new \Exception('Original image not found.');
            }

            $disk = config('filament-media-gallery.disk', 'public');

            if (Storage::disk($disk)->exists($image->path)) {
                Storage::disk($disk)->delete($image->path);
            }

            $path = config('filament-media-gallery.path', 'galeria');
            $newPath = $tempFile->store($path, $disk);

            $image->update([
                'path' => $newPath,
                'original_name' => $fileName,
                'size' => $tempFile->getSize(),
                'mime_type' => $tempFile->getMimeType(),
            ]);

            $this->data[$uploadKey] = null;

            Notification::make()
                ->success()
                ->title(__('filament-media-gallery::filament-media-gallery.notifications.image_updated.title'))
                ->body(__('filament-media-gallery::filament-media-gallery.notifications.image_updated.body'))
                ->send();

            \Log::info('ProcessaUploadGaleria: Image edited successfully', [
                'image_id' => $image->id
            ]);

        } catch (\Exception $e) {
            \Log::error('ProcessaUploadGaleria: Error in handleEditedMediaUpload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title(__('filament-media-gallery::filament-media-gallery.notifications.save_error.title'))
                ->body($e->getMessage())
                ->send();

            throw $e;
        }
    }

    /**
     * Loads more media for the gallery with pagination.
     * FILTERS BY MEDIA TYPE!
     */
    public function loadMoreMedias(int $page = 1, string $statePath): array
    {
        try {
            \Log::info('ProcessaUploadGaleria: Loading more medias', [
                'page' => $page,
                'statePath' => $statePath
            ]);

            $config = $this->getFieldConfig($statePath);

            if (!$config) {
                throw new \Exception("Unable to get configuration for field '$statePath'.");
            }

            $mediaType = $config['mediaType'];
            $modelClass = $config['modelClass'];
            $perPage = config('filament-media-gallery.gallery.per_page', 24);

            \Log::info('ProcessaUploadGaleria: Fetching medias', [
                'mediaType' => $mediaType,
                'modelClass' => $modelClass,
                'page' => $page,
                'perPage' => $perPage
            ]);

            // Fetch only from correct model (Image OR Video)
            $medias = $modelClass::orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $mappedMedias = collect($medias->items())->map(function ($media) use ($mediaType) {
                $data = [
                    'id' => $media->id,
                    'url' => $media->url,
                    'original_name' => $media->original_name,
                    'is_video' => $mediaType === 'video',
                ];

                // Add thumbnail_url for videos
                if ($mediaType === 'video' && method_exists($media, 'getThumbnailUrlAttribute')) {
                    $data['thumbnail_url'] = $media->thumbnail_url;
                }

                // Add alt text for images
                if ($mediaType === 'image' && isset($media->alt)) {
                    $data['alt'] = $media->alt;
                }

                return $data;
            })->toArray();

            \Log::info('ProcessaUploadGaleria: Medias loaded', [
                'mediaType' => $mediaType,
                'total' => count($mappedMedias),
                'hasMorePages' => $medias->hasMorePages()
            ]);

            return [
                'medias' => $mappedMedias,
                'hasMore' => $medias->hasMorePages(),
            ];
        } catch (\Exception $e) {
            \Log::error('ProcessaUploadGaleria: Error in loadMoreMedias', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['medias' => [], 'hasMore' => false];
        }
    }

    /**
     * Updates the alternative text (alt) of an image.
     */
    public function updateMediaAlt(int $mediaId, ?string $altText, string $statePath): void
    {
        try {
            \Log::info('ProcessaUploadGaleria: Updating alt text', [
                'mediaId' => $mediaId,
                'altText' => $altText,
                'statePath' => $statePath
            ]);

            $config = $this->getFieldConfig($statePath);

            if (!$config) {
                throw new \Exception("Unable to get configuration for field '$statePath'.");
            }

            $modelClass = $config['modelClass'];
            $mediaType = $config['mediaType'];

            // Alt text only makes sense for images
            if ($mediaType !== 'image') {
                \Log::warning('ProcessaUploadGaleria: Attempt to update alt on video', [
                    'mediaId' => $mediaId
                ]);
                return;
            }

            $image = $modelClass::find($mediaId);

            if (!$image) {
                throw new \Exception('Image not found.');
            }

            $image->update([
                'alt' => $altText
            ]);

            \Log::info('ProcessaUploadGaleria: Alt text updated successfully', [
                'image_id' => $image->id,
                'alt' => $altText
            ]);

        } catch (\Exception $e) {
            \Log::error('ProcessaUploadGaleria: Error in updateMediaAlt', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Gera um thumbnail para um arquivo de vídeo usando FFmpeg.
     *
     * @param string $videoPath Caminho absoluto para o arquivo de vídeo.
     * @return string|null Caminho do thumbnail gerado ou null em caso de falha.
     */
//    protected function gerarThumbnailVideo(string $videoPath): ?string
//    {
//        try {
//            $ffmpegPath = config('filament-media-gallery.video.ffmpeg_path', '/usr/bin/ffmpeg');
//            $ffprobePath = config('filament-media-gallery.video.ffprobe_path', '/usr/bin/ffprobe');
//
//            if (!file_exists($ffmpegPath) || !file_exists($ffprobePath)) {
//                \Log::error('FFmpeg ou FFprobe não encontrado nos caminhos especificados.');
//                return null;
//            }
//
//            $ffmpeg = \FFMpeg\FFMpeg::create([
//                'ffmpeg.binaries' => $ffmpegPath,
//                'ffprobe.binaries' => $ffprobePath,
//            ]);
//
//            // FFmpeg requires a file with a proper extension to work reliably, especially in tests.
//            // We rename the temp file to include a .mp4 extension before processing.
//            $videoPathWithExtension = $videoPath . '.mp4';
//            if (!rename($videoPath, $videoPathWithExtension)) {
//                \Log::error('Falha ao renomear arquivo de vídeo temporário para adicionar extensão.');
//                return null;
//            }
//
//            $video = $ffmpeg->open($videoPathWithExtension);
//
//            $thumbnailDir = config('filament-media-gallery.video.thumbnail.path', 'thumbnails');
//            // Use the original filename (without the temp path) to create the thumbnail name
//            $thumbnailFilename = pathinfo($videoPathWithExtension, PATHINFO_FILENAME) . '.jpg';
//            $thumbnailFullPath = Storage::disk('public')->path($thumbnailDir . '/' . $thumbnailFilename);
//
//            // Garante que o diretório de thumbnails exista
//            Storage::disk('public')->makeDirectory($thumbnailDir);
//
//            $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))->save($thumbnailFullPath);
//
//            // Clean up the renamed temp file
//            @unlink($videoPathWithExtension);
//
//            return $thumbnailDir . '/' . $thumbnailFilename;
//        } catch (\Exception $e) {
//            \Log::error('Falha ao gerar thumbnail do vídeo: ' . $e->getMessage());
//            return null;
//        }
//    }
}
