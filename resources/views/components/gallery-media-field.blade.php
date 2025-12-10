@php
    // Load Cropper.js assets
   \Filament\Support\Facades\FilamentAsset::register([
       \Filament\Support\Assets\Css::make('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css'),
       \Filament\Support\Assets\Js::make('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js'),
   ]);

   $mediaType = $getMediaType(); // 'image' or 'video'
   $modelClass = $getModelClass();
   $allowMultiple = $getAllowMultiple();
   $maxItems = $getMaxItems();
   $allowUpload = $getAllowUpload();
   $allowImageEditor = $getAllowImageEditor() && $mediaType === 'image'; // Editor only for images
   $imageEditorAspectRatios = $getImageEditorAspectRatios();
   $initialGalleryData = $getAvailableMedias();

   // Pass translations to JavaScript safely
   $translations = [
       'limit_reached' => [
           'title' => __('filament-media-gallery::filament-media-gallery.notifications.limit_reached.title'),
           'single' => __('filament-media-gallery::filament-media-gallery.notifications.limit_reached.single'),
       ],
       'processing_error' => [
           'title' => __('filament-media-gallery::filament-media-gallery.notifications.processing_error.title'),
           'body' => __('filament-media-gallery::filament-media-gallery.notifications.processing_error.body'),
       ],
       'upload_error' => [
           'title' => __('filament-media-gallery::filament-media-gallery.notifications.upload_error.title'),
           'body' => __('filament-media-gallery::filament-media-gallery.notifications.upload_error.body'),
       ],
       'image_edited_success' => [
           'title' => __('filament-media-gallery::filament-media-gallery.notifications.image_edited_success.title'),
           'body' => __('filament-media-gallery::filament-media-gallery.notifications.image_edited_success.body'),
       ],
       'save_error' => [
           'title' => __('filament-media-gallery::filament-media-gallery.notifications.save_error.title'),
           'body' => __('filament-media-gallery::filament-media-gallery.notifications.save_error.body'),
       ],
   ];

   // IMPORTANT: Fetch only CORRECT TYPE medias that are already selected
   $initiallySelectedMedias = $modelClass::find($getState() ?? [])->map(fn ($media) => [
       'id' => $media->id,
       'url' => $media->url,
       'original_name' => $media->original_name,
       'is_video' => $mediaType === 'video',
       'alt' => $mediaType === 'image' && isset($media->alt) ? $media->alt : null,
       'thumbnail_url' => $mediaType === 'video' && method_exists($media, 'getThumbnailUrlAttribute') ? $media->thumbnail_url : null,
   ]);

   $fieldId = 'galeria-midia-' . $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:key="{{ $fieldId }}"
        wire:ignore.self
        x-data="imageGalleryPicker({
            state: $wire.get('{{ $getStatePath() }}') || [],
            statePath: '{{ $getStatePath() }}',
            mediaType: @js($mediaType),
            initialMedias: @js($initialGalleryData['medias']),
            hasMorePages: @js($initialGalleryData['hasMore']),
            allowMultiple: @js($allowMultiple),
            maxItems: @js($maxItems),
            aspectRatios: @js($imageEditorAspectRatios ?? []),
            translations: @js($translations),
            selectedMedias: @js($initiallySelectedMedias)
        })"
        x-init="init()"
    >

        {{-- Selected Medias --}}
        <div x-show="selected.length > 0" class="g-section">
            <label class="g-label">
                <span x-text="mediaType === 'image' ? '📸 {{ __('filament-media-gallery::filament-media-gallery.labels.selected_images') }}' : '🎬 {{ __('filament-media-gallery::filament-media-gallery.labels.selected_videos') }}'"></span>
            </label>
            <div class="g-grid">
                {{-- Render available medias and Alpine controls visibility --}}
                <template x-for="media in availableMedias" :key="media.id">
                    <div x-show="isSelected(media.id)" class="g-thumbnail g-thumbnail-selected group">
                        {{-- Image Preview --}}
                        <template x-if="mediaType === 'image' && !media.is_video">
                            <img :src="media.url" :alt="media.alt || media.nome_original">
                        </template>
                        {{-- Video Preview (uses thumbnail if available) --}}
                        <template x-if="mediaType === 'video' && media.is_video">
                            <div class="g-video-preview">
                                <template x-if="media.thumbnail_url">
                                    <img :src="media.thumbnail_url" :alt="media.nome_original" class="g-video-thumbnail">
                                </template>
                                <template x-if="!media.thumbnail_url">
                                    <div class="g-video-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 4.5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-9a3 3 0 0 0-3-3h-15Zm10.253 5.747a.75.75 0 0 1 1.246-.547l3.001 2.25a.75.75 0 0 1 0 1.094l-3.001 2.25a.75.75 0 0 1-1.246-.547v-4.5Z"></path></svg>
                                    </div>
                                </template>
                                <div class="g-video-play-overlay">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </template>

                        <div class="g-thumbnail-actions">
                            @if($allowImageEditor)
                                <button type="button" x-show="mediaType === 'image' && !media.is_video"
                                        @click.stop="openEditor(media.id, media.url)"
                                        title="{{ __('filament-media-gallery::filament-media-gallery.buttons.edit_image') }}"
                                        class="g-thumbnail-btn-edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            @endif

                            <button type="button"
                                    @click.stop="removeMedia(media.id)"
                                    title="{{ __('filament-media-gallery::filament-media-gallery.buttons.remove_item') }}"
                                    class="g-thumbnail-btn-remove">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="g-thumbnail-name" x-text="media.nome_original"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Alt Text Area for Selected Images --}}
        <div x-show="selected.length > 0 && mediaType === 'image'" class="g-alt-section">
            <div class="g-alt-section-title">
                ✏️ {{ __('filament-media-gallery::filament-media-gallery.labels.alt_texts') }}
            </div>

            <template x-for="media in availableMedias" :key="'alt-' + media.id">
                <div x-show="isSelected(media.id) && !media.is_video" class="g-alt-item">
                    <!-- Image Thumbnail -->
                    <div class="g-alt-item-thumbnail">
                        <img :src="media.url" :alt="media.alt || media.nome_original">
                    </div>

                    <!-- Content: Name + Input -->
                    <div class="g-alt-item-content">
                        <div class="g-alt-item-name" x-text="media.nome_original"></div>

                        <div class="g-alt-input-wrapper">
                            <label class="g-alt-label" :for="'alt-input-' + media.id">
                                {{ __('filament-media-gallery::filament-media-gallery.labels.alternative_text') }}
                            </label>
                            <input
                                type="text"
                                :id="'alt-input-' + media.id"
                                class="g-alt-input"
                                :value="media.alt || ''"
                                @input="updateAltText(media.id, $event.target.value)"
                                :placeholder="__('filament-media-gallery::filament-media-gallery.placeholders.describe_image')"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Files Uploaded for Upload --}}
        @if($allowUpload)
            <div x-show="uploadedFiles.length > 0" class="g-section">
                <label class="g-label">
                    📁 {{ __('filament-media-gallery::filament-media-gallery.labels.ready_for_upload') }}
                </label>
                <div class="g-grid">
                    <template x-for="(file, index) in uploadedFiles" :key="file.name">
                        <div class="g-thumbnail g-thumbnail-upload group">
                            {{-- Conditional preview based on type --}}
                            <template x-if="mediaType === 'image'">
                                <img :src="URL.createObjectURL(file)" :alt="file.name">
                            </template>
                            <template x-if="mediaType === 'video'">
                                <div class="g-video-placeholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 4.5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-9a3 3 0 0 0-3-3h-15Zm10.253 5.747a.75.75 0 0 1 1.246-.547l3.001 2.25a.75.75 0 0 1 0 1.094l-3.001 2.25a.75.75 0 0 1-1.246-.547v-4.5Z"></path></svg>
                                </div>
                            </template>
                            <button type="button"
                                    @click="removeUploadedFile(index)"
                                    class="g-thumbnail-btn-remove">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <div class="g-thumbnail-name" x-text="file.name"></div>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        {{-- Action Buttons Area - SEPARATED --}}
        <div class="g-actions-container">
            <div class="g-actions-title">
                ⚡ {{ __('filament-media-gallery::filament-media-gallery.labels.available_actions') }}
            </div>

            {{-- UPLOAD PROGRESS INDICATOR --}}
            <div x-show="uploading"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="g-upload-progress">
                <div class="g-upload-spinner"></div>
                <span x-text="uploadProgress"></span>
            </div>

            <div class="g-actions">
                <button type="button"
                        @click="modalOpen = true"
                        class="g-btn g-btn-primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span x-text="mediaType === 'image' ? '{{ __('filament-media-gallery::filament-media-gallery.buttons.select_from_gallery') }}' : '{{ __('filament-media-gallery::filament-media-gallery.buttons.select_from_videos') }}'"></span>
                </button>

                @if($allowUpload)
                    <label class="g-btn g-btn-success" :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span x-text="mediaType === 'image' ? '{{ __('filament-media-gallery::filament-media-gallery.buttons.upload_images') }}' : '{{ __('filament-media-gallery::filament-media-gallery.buttons.upload_videos') }}'"></span>
                        <input type="file"
                               :accept="mediaType === 'image' ? 'image/png,image/jpeg,image/jpg,image/webp' : 'video/mp4,video/webm'"
                               @change="handleMediaUpload($event)"
                               :disabled="uploading"
                               class="g-hidden-input">
                    </label>
                @endif
            </div>
        </div>

        {{-- Gallery Modal --}}
        <div x-show="modalOpen"
             x-cloak
             class="g-modal-overlay"
             style="display: none;">
            <div class="g-modal-container">
                <div @click.away="modalOpen = false" class="g-modal-content">
                    <div class="g-modal-header">
                        <h3 class="g-modal-title">
                            <span x-text="mediaType === 'image' ? '{{ __('filament-media-gallery::filament-media-gallery.labels.image_gallery') }}' : '{{ __('filament-media-gallery::filament-media-gallery.labels.video_gallery') }}'"></span>
                        </h3>
                        <button type="button" @click="modalOpen = false" class="g-modal-close-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="g-modal-grid">
                        <template x-if="availableMedias.length > 0">
                            <template x-for="media in availableMedias" :key="media.id">
                                <div @click="toggleMedia(media.id)"
                                     :class="{ 'g-modal-thumb-selected': isSelected(media.id) }"
                                     class="g-modal-thumb">
                                    {{-- Display image OR video based on field mediaType --}}
                                    <template x-if="mediaType === 'image' && !media.is_video">
                                        <img :src="media.url" :alt="media.alt || media.nome_original" class="g-modal-thumb-img">
                                    </template>
                                    <template x-if="mediaType === 'video' && media.is_video">
                                        <div class="g-video-preview g-modal-video-preview">
                                            <template x-if="media.thumbnail_url">
                                                <img :src="media.thumbnail_url" :alt="media.nome_original" class="g-modal-thumb-img">
                                            </template>
                                            <template x-if="!media.thumbnail_url">
                                                <div class="g-video-placeholder g-video-placeholder-modal">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 4.5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-9a3 3 0 0 0-3-3h-15Zm10.253 5.747a.75.75 0 0 1 1.246-.547l3.001 2.25a.75.75 0 0 1 0 1.094l-3.001 2.25a.75.75 0 0 1-1.246-.547v-4.5Z"></path></svg>
                                                </div>
                                            </template>
                                            <div class="g-video-play-overlay">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="isSelected(media.id)"
                                         class="g-modal-thumb-check">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="g-modal-thumb-name" x-text="media.nome_original"></div>
                                </div>
                            </template>
                        </template>
                        <template x-if="availableMedias.length === 0">
                            <div class="g-modal-empty">
                                <span x-text="mediaType === 'image' ? '{{ __('filament-media-gallery::filament-media-gallery.empty.no_images') }}' : '{{ __('filament-media-gallery::filament-media-gallery.empty.no_videos') }}'"></span>
                            </div>
                        </template>
                    </div>

                    <div class="g-modal-footer">
                        <button type="button"
                                @click="modalOpen = false"
                                class="g-btn g-btn-primary">
                            {{ __('filament-media-gallery::filament-media-gallery.buttons.confirm_selection') }}
                        </button>
                        <button type="button"
                                x-show="hasMorePages"
                                @click="loadMore()"
                                :disabled="loadingMore"
                                class="g-btn g-btn-secondary"
                                x-text="loadingMore ? '{{ __('filament-media-gallery::filament-media-gallery.buttons.loading') }}' : '{{ __('filament-media-gallery::filament-media-gallery.buttons.load_more') }}'">
                            {{ __('filament-media-gallery::filament-media-gallery.buttons.load_more') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Image Editor Modal (ONLY FOR IMAGES) --}}
        @if($allowImageEditor)
            <div x-show="editorOpen" x-cloak class="g-modal-overlay" style="display: none;">
                <div class="g-modal-container">
                    <div @click.away="closeEditor()" class="g-modal-content g-modal-editor">

                        <div class="g-modal-header">
                            <h3 class="g-modal-title">{{ __('filament-media-gallery::filament-media-gallery.labels.image_editor') }}</h3>
                            <button type="button" @click="closeEditor()" class="g-modal-close-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Editor Toolbar -->
                        <div class="g-editor-toolbar">
                            <div class="g-toolbar-group">
                                <button type="button" @click="resetImage()" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.reset') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="g-toolbar-group">
                                <button type="button" @click="rotate(-90)" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.rotate_left') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="rotate(90)" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.rotate_right') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="g-toolbar-group">
                                <button type="button" @click="flipHorizontal()" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.flip_horizontal') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="flipVertical()" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.flip_vertical') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="g-toolbar-group">
                                <button type="button" @click="zoom(0.1)" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.zoom_in') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="zoom(-0.1)" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.zoom_out') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                                    </svg>
                                </button>
                            </div>

                            <template x-if="aspectRatios && aspectRatios.length > 0">
                                <div class="g-toolbar-group">
                                    <label class="g-toolbar-label">{{ __('filament-media-gallery::filament-media-gallery.editor.aspect_ratio') }}:</label>
                                    <select @change="changeAspectRatio($event.target.value)" class="g-toolbar-select">
                                        <option value="free" :selected="currentAspectRatio === 'free'">{{ __('filament-media-gallery::filament-media-gallery.editor.free') }}</option>
                                        <template x-for="(ratio, index) in aspectRatios" :key="index">
                                            <option :value="ratio" :selected="currentAspectRatio === ratio" x-text="ratio"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                        </div>

                        <div class="g-editor-container">
                            <div class="g-editor-wrapper">
                                <img x-ref="imageEditorCanvas" class="g-editor-canvas">
                            </div>
                        </div>

                        <div class="g-modal-footer">
                            <button type="button"
                                    @click="closeEditor()"
                                    class="g-btn g-btn-secondary">
                                {{ __('filament-media-gallery::filament-media-gallery.buttons.cancel') }}
                            </button>
                            <button type="button"
                                    @click="saveEditedImage()"
                                    class="g-btn g-btn-primary">
                                {{ __('filament-media-gallery::filament-media-gallery.buttons.save_changes') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-dynamic-component>

<style>
    /* [TODO O CSS ANTERIOR PERMANECE IGUAL] */
    /* Container Principal */
    .g-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        padding: 1rem;
    }

    /* Seções de Conteúdo */
    .g-section {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background-color: rgb(249, 250, 251);
        padding: 1.5rem;
        border-radius: 0.5rem;
    }

    .dark .g-section {
        background-color: rgb(31, 41, 55);
    }

    .g-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(55, 65, 81);
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid rgb(229, 231, 235);
        padding-bottom: 0.5rem;
    }

    .dark .g-label {
        color: rgb(209, 213, 219);
        border-bottom-color: rgb(75, 85, 99);
    }

    /* Grid de Imagens - CENTRALIZADO */
    .g-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        align-items: flex-start;
        padding: 1rem 0;
    }

    /* Thumbnails */
    .g-thumbnail {
        position: relative;
        border-radius: 0.5rem;
        overflow: hidden;
        width: 150px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }

    .g-thumbnail:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .g-thumbnail img {
        width: 100%;
        height: 10rem;
        object-fit: cover;
        display: block;
    }

    .g-video-placeholder {
        width: 100%;
        height: 10rem;
        background-color: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
    }
    .dark .g-video-placeholder {
        background-color: #374151;
        color: #6b7280;
    }
    .g-video-placeholder svg {
        width: 4rem;
        height: 4rem;
    }

    /* Preview de Vídeo com Thumbnail */
    .g-video-preview {
        position: relative;
        width: 100%;
        height: 10rem;
        overflow: hidden;
    }

    .g-video-thumbnail {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .g-video-play-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 4rem;
        height: 4rem;
        background-color: rgba(0, 0, 0, 0.7);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        pointer-events: none;
    }

    .g-video-play-overlay svg {
        width: 2rem;
        height: 2rem;
        margin-left: 0.25rem;
    }

    .g-thumbnail:hover .g-video-play-overlay,
    .g-modal-thumb:hover .g-video-play-overlay {
        background-color: rgba(37, 99, 235, 0.9);
        transform: translate(-50%, -50%) scale(1.1);
    }

    .g-modal-video-preview {
        height: 8rem;
    }

    .g-thumbnail-selected {
        border: 3px solid rgb(59, 130, 246);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .g-thumbnail-upload {
        border: 3px solid rgb(22, 163, 74);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
    }

    /* Ações dos Thumbnails */
    .g-thumbnail-actions {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        display: flex;
        gap: 0.25rem;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 10;
    }

    .g-thumbnail.group:hover .g-thumbnail-actions {
        opacity: 1;
    }

    .g-thumbnail-btn-edit,
    .g-thumbnail-btn-remove {
        background-color: rgba(17, 24, 39, 0.8);
        color: white;
        border-radius: 0.375rem;
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        backdrop-filter: blur(4px);
    }

    .g-thumbnail-btn-edit:hover {
        background-color: rgb(37, 99, 235);
        transform: scale(1.1);
    }

    .g-thumbnail-btn-remove:hover {
        background-color: rgb(220, 38, 38);
        transform: scale(1.1);
    }

    .g-thumbnail-btn-edit svg,
    .g-thumbnail-btn-remove svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    /* Nome do Thumbnail */
    .g-thumbnail-name {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        color: white;
        font-size: 0.75rem;
        padding: 1rem 0.5rem 0.5rem 0.5rem;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    /* Área de Botões de Ação */
    .g-actions-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.5rem;
        background-color: white;
        border-radius: 0.5rem;
        border: 2px dashed rgb(229, 231, 235);
    }

    .dark .g-actions-container {
        background-color: rgb(31, 41, 55);
        border-color: rgb(75, 85, 99);
    }

    .g-actions-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(107, 114, 128);
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .dark .g-actions-title {
        color: rgb(156, 163, 175);
    }

    .g-actions {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* Botões */
    .g-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        border: 1px solid transparent;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        min-width: 200px;
    }

    .g-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .g-btn:active {
        transform: translateY(0);
    }

    .g-btn svg {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
        flex-shrink: 0;
    }

    .g-btn-primary {
        background-color: rgb(37, 99, 235);
    }

    .g-btn-primary:hover {
        background-color: rgb(29, 78, 216);
    }

    .g-btn-secondary {
        background-color: rgb(107, 114, 128);
        color: white;
    }

    .g-btn-secondary:hover {
        background-color: rgb(75, 85, 99);
    }

    .g-btn-success {
        background-color: rgb(21, 128, 61);
    }

    .g-btn-success:hover {
        background-color: rgb(22, 101, 52);
    }

    .g-hidden-input {
        display: none;
    }

    /* Modal */
    .g-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 50;
        overflow-y: auto;
        background-color: rgba(17, 24, 39, 0.75);
        backdrop-filter: blur(4px);
    }

    .g-modal-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 1rem;
    }

    .g-modal-content {
        position: relative;
        background-color: white;
        border-radius: 0.75rem;
        max-width: 72rem;
        width: 100%;
        padding: 1.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .dark .g-modal-content {
        background-color: rgb(31, 41, 55);
    }

    .g-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid rgb(229, 231, 235);
    }

    .dark .g-modal-header {
        border-bottom-color: rgb(75, 85, 99);
    }

    .g-modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: rgb(17, 24, 39);
    }

    .dark .g-modal-title {
        color: white;
    }

    .g-modal-close-btn {
        color: rgb(156, 163, 175);
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        padding: 0.25rem;
        border-radius: 0.375rem;
    }

    .g-modal-close-btn:hover {
        color: rgb(107, 114, 128);
        background-color: rgb(243, 244, 246);
    }

    .dark .g-modal-close-btn:hover {
        background-color: rgb(55, 65, 81);
    }

    .g-modal-close-btn svg {
        width: 1.5rem;
        height: 1.5rem;
    }

    .g-modal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        max-height: 60vh;
        overflow-y: auto;
        padding: 0.5rem;
    }

    .g-modal-thumb {
        position: relative;
        cursor: pointer;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 2px solid rgb(229, 231, 235);
        transition: all 0.2s;
    }

    .g-modal-thumb:hover {
        border-color: rgb(147, 197, 253);
        transform: scale(1.02);
    }

    .g-modal-thumb-selected {
        border: 4px solid rgb(59, 130, 246);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .g-modal-thumb-img {
        width: 100%;
        height: 8rem;
        object-fit: cover;
    }

    .g-video-placeholder-modal {
        height: 8rem;
    }

    .g-modal-thumb-check {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background-color: rgb(37, 99, 235);
        color: white;
        border-radius: 9999px;
        width: 1.75rem;
        height: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .g-modal-thumb-check svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .g-modal-thumb-name {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
        color: white;
        font-size: 0.75rem;
        padding: 1rem 0.5rem 0.5rem 0.5rem;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    .g-modal-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1rem;
        color: rgb(107, 114, 128);
    }

    .g-modal-footer {
        margin-top: 1.5rem;
        padding-top: 1rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        border-top: 2px solid rgb(229, 231, 235);
    }

    .dark .g-modal-footer {
        border-top-color: rgb(75, 85, 99);
    }

    /* Editor */
    .g-modal-editor {
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .g-editor-container {
        flex-grow: 1;
        height: 60vh;
        max-height: 60vh;
        background-color: rgb(249, 250, 251);
        border-radius: 0.5rem;
        padding: 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        position: relative;
    }

    .dark .g-editor-container {
        background-color: rgb(17, 24, 39);
    }

    .g-editor-wrapper {
        width: 100%;
        height: 100%;
        max-width: 100%;
        max-height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        position: relative;
    }

    .g-editor-canvas {
        max-width: 100%;
        max-height: 100%;
        display: block;
        margin: 0 auto;
    }

    .g-editor-wrapper .cropper-container {
        max-width: 100% !important;
        max-height: 100% !important;
    }

    .g-editor-toolbar {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background-color: rgb(243, 244, 246);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
    }

    .dark .g-editor-toolbar {
        background-color: rgb(55, 65, 81);
    }

    .g-toolbar-group {
        display: flex;
        gap: 0.25rem;
        align-items: center;
    }

    .g-toolbar-btn {
        padding: 0.5rem;
        background-color: white;
        border: 1px solid rgb(209, 213, 219);
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .g-toolbar-btn:hover {
        background-color: rgb(243, 244, 246);
        border-color: rgb(156, 163, 175);
        transform: scale(1.05);
    }

    .dark .g-toolbar-btn {
        background-color: rgb(31, 41, 55);
        border-color: rgb(75, 85, 99);
    }

    .dark .g-toolbar-btn:hover {
        background-color: rgb(55, 65, 81);
    }

    .g-toolbar-btn svg {
        width: 1.25rem;
        height: 1.25rem;
        color: rgb(55, 65, 81);
    }

    .dark .g-toolbar-btn svg {
        color: rgb(209, 213, 219);
    }

    .g-toolbar-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(55, 65, 81);
        margin-right: 0.5rem;
    }

    .dark .g-toolbar-label {
        color: rgb(209, 213, 219);
    }

    .g-toolbar-select {
        padding: 0.5rem;
        background-color: white;
        border: 1px solid rgb(209, 213, 219);
        border-radius: 0.375rem;
        font-size: 0.875rem;
        cursor: pointer;
    }

    .dark .g-toolbar-select {
        background-color: rgb(31, 41, 55);
        border-color: rgb(75, 85, 99);
        color: white;
    }

    /* Upload Progress */
    .g-upload-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 1rem;
        background: linear-gradient(135deg, rgb(219, 234, 254) 0%, rgb(191, 219, 254) 100%);
        border-radius: 0.5rem;
        color: rgb(37, 99, 235);
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
        border: 1px solid rgb(191, 219, 254);
    }

    .dark .g-upload-progress {
        background: linear-gradient(135deg, rgb(30, 58, 138) 0%, rgb(23, 37, 84) 100%);
        color: rgb(147, 197, 253);
        border-color: rgb(30, 64, 175);
    }

    .g-upload-spinner {
        border: 3px solid rgb(191, 219, 254);
        border-top-color: rgb(37, 99, 235);
        border-radius: 50%;
        width: 1.5rem;
        height: 1.5rem;
        animation: spin 0.8s linear infinite;
    }

    .dark .g-upload-spinner {
        border-color: rgb(30, 64, 175);
        border-top-color: rgb(147, 197, 253);
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .opacity-50 {
        opacity: 0.5;
    }

    .cursor-not-allowed {
        cursor: not-allowed;
    }

    .cursor-not-allowed input {
        cursor: not-allowed;
    }
    /* Seção de Alt Text - Área Separada */
    .g-alt-section {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background-color: rgb(249, 250, 251);
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .dark .g-alt-section {
        background-color: rgb(31, 41, 55);
    }

    .g-alt-section-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(55, 65, 81);
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid rgb(229, 231, 235);
        padding-bottom: 0.5rem;
    }

    .dark .g-alt-section-title {
        color: rgb(209, 213, 219);
        border-bottom-color: rgb(75, 85, 99);
    }

    .g-alt-item {
        display: flex;
        gap: 1rem;
        align-items: center;
        padding: 1rem;
        background-color: white;
        border-radius: 0.5rem;
        border: 1px solid rgb(229, 231, 235);
        transition: all 0.2s;
    }

    .dark .g-alt-item {
        background-color: rgb(55, 65, 81);
        border-color: rgb(75, 85, 99);
    }

    .g-alt-item:hover {
        border-color: rgb(59, 130, 246);
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
    }

    .g-alt-item-thumbnail {
        width: 80px;
        height: 80px;
        border-radius: 0.375rem;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid rgb(229, 231, 235);
    }

    .dark .g-alt-item-thumbnail {
        border-color: rgb(75, 85, 99);
    }

    .g-alt-item-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .g-alt-item-content {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .g-alt-item-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(17, 24, 39);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dark .g-alt-item-name {
        color: rgb(229, 231, 235);
    }

    .g-alt-input-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .g-alt-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgb(75, 85, 99);
    }

    .dark .g-alt-label {
        color: rgb(156, 163, 175);
    }

    .g-alt-input {
        width: 100%;
        padding: 0.625rem;
        font-size: 0.875rem;
        border: 1px solid rgb(209, 213, 219);
        border-radius: 0.375rem;
        background-color: white;
        color: rgb(17, 24, 39);
        transition: all 0.2s;
    }

    .g-alt-input:focus {
        outline: none;
        border-color: rgb(59, 130, 246);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .dark .g-alt-input {
        background-color: rgb(31, 41, 55);
        border-color: rgb(75, 85, 99);
        color: white;
    }

    .dark .g-alt-input:focus {
        border-color: rgb(96, 165, 250);
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
    }
</style>

<script>
    function imageGalleryPicker(config) {
        return {
                selected: config.state,
                availableMedias: config.initialMedias,
                modalOpen: false,
                mediaType: config.mediaType, // 'image' or 'video'
                uploadedFiles: [],
                editorOpen: false,
                cropper: null,
                imageToEditId: null,
                imageToEditUrl: null,
                fileToEdit: null,
                aspectRatios: config.aspectRatios,
                currentAspectRatio: config.aspectRatios && config.aspectRatios.length > 0 ? config.aspectRatios[0] : 'free',
                uploading: false,
                uploadProgress: '',
                currentPage: 1,
                hasMorePages: config.hasMorePages,
                loadingMore: false,

                init() {
                    console.log('🖼️ Gallery Initialized - Type:', this.mediaType, 'Medias:', this.availableMedias.length);
                    console.log('Initial state:', JSON.parse(JSON.stringify(this.selected)));

                    // Merge selected medias with alt text to available data (if exists)
                    if (config.selectedMedias && config.selectedMedias.length > 0) {
                        config.selectedMedias.forEach(selectedMedia => {
                            const existingIndex = this.availableMedias.findIndex(m => m.id === selectedMedia.id);
                            if (existingIndex !== -1) {
                                // Update existing media with complete data (including alt)
                                this.availableMedias[existingIndex] = {
                                    ...this.availableMedias[existingIndex],
                                    ...selectedMedia
                                };
                            } else {
                                // Add media if doesn't exist
                                this.availableMedias.push(selectedMedia);
                            }
                        });
                        console.log('✅ Selected medias merged with alt text');
                    }

                    // Watch for Livewire state changes
                    this.$watch('$wire.get(\'' + config.statePath + '\')', (newState) => {
                        this.selected = newState || [];
                    });

                    // LISTENER 1: Receive filtered medias by type (used in loadMore)
                    Livewire.on('galeria:medias-updated', ({ medias }) => {
                        console.log('🔄 Receiving filtered medias:', medias);
                        medias.forEach(galleryMedia => {
                            // Only add if correct type
                            if (galleryMedia.is_video === (this.mediaType === 'video')) {
                                const existingIndex = this.availableMedias.findIndex(m => m.id === galleryMedia.id);
                                if (existingIndex === -1) {
                                    this.availableMedias.push(galleryMedia);
                                }
                            }
                        });
                    });

                    // LISTENER 2: New media added (upload or edit)
                    Livewire.on('galeria:media-added', ({ media }) => {
                        console.log('✨ New media added:', media);

                        // Check if it's the correct type before adding
                        if (media.is_video === (this.mediaType === 'video')) {
                            const existingIndex = this.availableMedias.findIndex(m => m.id === media.id);

                            if (existingIndex !== -1) {
                                // Update existing media (edit case)
                                this.availableMedias[existingIndex] = {
                                    ...this.availableMedias[existingIndex],
                                    ...media
                                };
                                console.log('🔄 Media updated:', media.id);
                            } else {
                                // Add new media
                                this.availableMedias.push(media);
                                console.log('➕ New media added to list:', media.id);
                            }

                            // Auto-select if not multiple
                            if (!config.allowMultiple) {
                                this.selected = [media.id];
                                this.$wire.set(config.statePath, this.selected);
                            } else if (config.allowMultiple && !this.isSelected(media.id)) {
                                // Auto-select if multiple and hasn't reached limit
                                if (!config.maxItems || this.selected.length < config.maxItems) {
                                    this.selected.push(media.id);
                                    this.$wire.set(config.statePath, this.selected);
                                }
                            }
                        }
                    });
                },

                loadMore() {
                    if (this.loadingMore || !this.hasMorePages) return;

                    this.loadingMore = true;
                    this.currentPage++;

                    console.log(`📄 Loading page ${this.currentPage} of ${this.mediaType}...`);

                    this.$wire.call('loadMoreMedias', this.currentPage, config.statePath).then(result => {
                        // Filter only correct type (extra protection)
                        const filteredMedias = result.medias.filter(m =>
                            m.is_video === (this.mediaType === 'video')
                        );

                        this.availableMedias.push(...filteredMedias);
                        this.hasMorePages = result.hasMore;
                        this.loadingMore = false;
                        console.log(`✅ Page ${this.currentPage} loaded. Total: ${this.availableMedias.length}`);
                    }).catch(error => {
                        console.error('❌ Error loading more medias:', error);
                        this.loadingMore = false;
                    });
                },

                toggleMedia(mediaId) {
                    console.log(`🔄 Toggling media: ${mediaId}`);

                    // Fetch complete media with alt text
                    const media = this.availableMedias.find(m => m.id === mediaId);
                    if (media && media.alt) {
                        console.log('✅ Selected media has alt text:', media.alt);
                    }

                    if (config.allowMultiple) {
                        const index = this.selected.indexOf(mediaId);
                        if (index > -1) {
                            this.selected.splice(index, 1);
                            console.log('➖ Media removed from selection');
                        } else {
                            if (config.maxItems && this.selected.length >= config.maxItems) {
                                console.warn('⚠️ Maximum items reached:', config.maxItems);
                                new FilamentNotification()
                                    .title(config.translations.limit_reached.title)
                                    .warning()
                                    .body(config.translations.limit_reached.body ||
                                        'Maximum of ' + config.maxItems + (this.mediaType === 'image' ? ' images' : ' videos') + ' allowed')
                                    .send();
                                return;
                            }
                            this.selected.push(mediaId);
                            console.log('➕ Media added to selection');
                        }
                    } else {
                        this.selected = this.isSelected(mediaId) ? [] : [mediaId];
                        console.log('🔄 Single selection updated');
                    }

                    console.log('📊 State after toggle:', JSON.parse(JSON.stringify(this.selected)));
                    this.$wire.set(config.statePath, this.selected);
                },

                removeMedia(mediaId) {
                    const index = this.selected.indexOf(mediaId);
                    console.log(`🗑️ Removing media: ${mediaId}, index: ${index}`);

                    if (index > -1) {
                        this.selected.splice(index, 1);
                        console.log('✅ Media removed from selection');
                    }

                    this.$wire.set(config.statePath, this.selected);
                },

                isSelected(mediaId) {
                    const numericId = parseInt(mediaId, 10);
                    return this.selected.map(id => parseInt(id, 10)).includes(numericId);
                },

                handleMediaUpload(event) {
                    const file = event.target.files[0];
                    console.log('📤 Upload started:', file?.name);

                    if (!file) {
                        console.warn('⚠️ No file selected');
                        return;
                    }

                    // Check single selection limit
                    if (!config.allowMultiple && this.selected.length > 0) {
                        new FilamentNotification()
                            .title(config.translations.limit_reached.title)
                            .warning()
                            .body(config.translations.limit_reached.single)
                            .send();
                        event.target.value = '';
                        return;
                    }

                    this.uploading = true;
                    this.uploadProgress = `Uploading ${file.name}...`;

                    // Property name where Livewire will store the file
                    const uploadPropertyName = config.statePath + '_new_media';
                    console.log('📦 Upload property name:', uploadPropertyName);

                    this.$wire.upload(
                        uploadPropertyName,
                        file,
                        (uploadedFilename) => {
                            console.log('✅ Upload completed:', uploadedFilename);
                            console.log('🔧 Calling handleNewMediaUpload...');

                            this.$wire.call('handleNewMediaUpload', uploadedFilename, config.statePath)
                                .then(() => {
                                    console.log('✨ Processing completed successfully');
                                    this.uploading = false;
                                    this.uploadProgress = '';
                                    event.target.value = '';
                                })
                                .catch((error) => {
                                    console.error('❌ Processing error:', error);
                                    this.uploading = false;
                                    this.uploadProgress = '';
                                    event.target.value = '';
                                    new FilamentNotification()
                                        .title(config.translations.processing_error.title)
                                        .danger()
                                        .body(config.translations.processing_error.body)
                                        .send();
                                });
                        },
                        (error) => {
                            console.error('❌ Upload error:', error);
                            this.uploading = false;
                            this.uploadProgress = '';
                            event.target.value = '';
                            new FilamentNotification()
                                .title(config.translations.upload_error.title)
                                .danger()
                                .body(config.translations.upload_error.body)
                                .send();
                        },
                        (event) => {
                            const progress = Math.round(event.detail.progress);
                            this.uploadProgress = `Uploading: ${progress}%`;
                            console.log(`📊 Progress: ${progress}%`);
                        }
                    );
                },

                removeUploadedFile(index) {
                    console.log(`🗑️ Removing file at index: ${index}`);
                    this.uploadedFiles.splice(index, 1);
                },

                async openEditor(imageId, imageUrl) {
                    // Editor only works for images
                    if (this.mediaType !== 'image') {
                        console.warn('⚠️ Editor available only for images');
                        return;
                    }

                    console.log(`🖌️ Opening editor - ID: ${imageId}`);
                    this.imageToEditId = imageId;
                    this.imageToEditUrl = imageUrl;

                    try {
                        const response = await fetch(imageUrl);
                        const blob = await response.blob();
                        const file = new File([blob], imageUrl.split('/').pop(), { type: blob.type });
                        this.fileToEdit = file;

                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.$refs.imageEditorCanvas.src = e.target.result;
                            this.editorOpen = true;
                            this.$nextTick(() => this.initCropper());
                        };
                        reader.readAsDataURL(file);
                    } catch (error) {
                        console.error('❌ Error loading image:', error);
                        new FilamentNotification()
                            .title(config.translations.save_error.title || 'Loading Error')
                            .danger()
                            .body('Unable to load image.')
                            .send();
                    }
                },

                closeEditor() {
                    console.log('🚪 Closing editor.');
                    this.editorOpen = false;
                    if (this.cropper) {
                        this.cropper.destroy();
                        this.cropper = null;
                    }
                    this.$refs.imageEditorCanvas.src = '';
                    this.imageToEditId = null;
                    this.imageToEditUrl = null;
                    this.fileToEdit = null;
                },

                initCropper() {
                    console.log('🔧 Initializing Cropper.js');
                    if (this.cropper) {
                        this.cropper.destroy();
                    }
                    this.cropper = new Cropper(this.$refs.imageEditorCanvas, {
                        aspectRatio: this.getAspectRatioValue(this.currentAspectRatio),
                        viewMode: 2,
                        dragMode: 'move',
                        autoCropArea: 0.9,
                        responsive: true,
                        restore: false,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        minContainerWidth: 300,
                        minContainerHeight: 200,
                    });
                },

                getAspectRatioValue(ratioString) {
                    if (!ratioString || ratioString === 'free') return NaN;
                    const parts = ratioString.split(':');
                    return parseFloat(parts[0]) / parseFloat(parts[1]);
                },

                resetImage() {
                    if (this.cropper) this.cropper.reset();
                },

                rotate(degree) {
                    if (this.cropper) this.cropper.rotate(degree);
                },

                flipHorizontal() {
                    if (this.cropper) this.cropper.scaleX(-this.cropper.getData().scaleX || -1);
                },

                flipVertical() {
                    if (this.cropper) this.cropper.scaleY(-this.cropper.getData().scaleY || -1);
                },

                zoom(factor) {
                    if (this.cropper) this.cropper.zoom(factor);
                },

                changeAspectRatio(ratioString) {
                    this.currentAspectRatio = ratioString;
                    if (this.cropper) this.cropper.setAspectRatio(this.getAspectRatioValue(ratioString));
                },

                saveEditedImage() {
                    console.log('💾 Saving edited image...');
                    if (!this.cropper) return;

                    this.cropper.getCroppedCanvas().toBlob((blob) => {
                        console.log('🖼️ Canvas converted to Blob');
                        const fileName = `${this.fileToEdit.name.split('.').slice(0, -1).join('.')}_edited.png`;

                        this.$wire.upload(config.statePath + '_edited_media', blob, () => {
                            console.log('✅ Edited image upload completed');
                            this.$wire.call('handleEditedMediaUpload', this.imageToEditId, fileName, config.statePath)
                                .then(() => {
                                    console.log('✨ Image updated successfully');
                                    this.closeEditor();
                                    new FilamentNotification()
                                        .title(config.translations.image_edited_success.title)
                                        .success()
                                        .body(config.translations.image_edited_success.body)
                                        .send();
                                    this.$wire.$refresh();
                                })
                                .catch((error) => {
                                    console.error('❌ Save error:', error);
                                    new FilamentNotification()
                                        .title(config.translations.save_error.title)
                                        .danger()
                                        .body(config.translations.save_error.body)
                                        .send();
                                });
                        });
                    }, 'image/png');
                },

                updateAltText(mediaId, altText) {
                    console.log(`✏️ Updating alt text - ID: ${mediaId}`, altText);

                    // Update alt text locally
                    const media = this.availableMedias.find(m => m.id === mediaId);
                    if (media) {
                        media.alt = altText;
                        console.log('✅ Alt text updated locally');
                    }

                    // Persist in backend via Livewire
                    this.$wire.call('updateMediaAlt', mediaId, altText, config.statePath)
                        .then(() => {
                            console.log('✅ Alt text persisted in backend');
                        })
                        .catch((error) => {
                            console.error('❌ Error updating alt text:', error);
                        });
                },
            };

    }
</script>
