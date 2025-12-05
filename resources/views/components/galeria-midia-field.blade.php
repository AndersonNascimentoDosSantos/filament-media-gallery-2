@php
    // Carrega os assets do Cropper.js
   \Filament\Support\Facades\FilamentAsset::register([
       \Filament\Support\Assets\Css::make('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css'),
       \Filament\Support\Assets\Js::make('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js'),
   ]);

   $mediaType = $getMediaType(); // 'image' ou 'video'
   $modelClass = $getModelClass();
   $allowMultiple = $getAllowMultiple();
   $maxItems = $getMaxItems();
   $allowUpload = $getAllowUpload();
   $allowImageEditor = $getAllowImageEditor() && $mediaType === 'image'; // Editor só para imagens
    $imageEditorAspectRatios = $getImageEditorAspectRatios();
    $dadosIniciaisGaleria = $getMediasDisponiveis();

     // Passa as traduções para o JavaScript de forma segura
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

   // IMPORTANTE: Busca apenas as mídias DO TIPO CORRETO que já estão selecionadas
   $mediasSelecionadasInicialmente = $modelClass::find($getState() ?? [])->map(fn ($media) => [
       'id' => $media->id,
       'url' => $media->url,
       'nome_original' => $media->nome_original,
       'is_video' => $mediaType === 'video',
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
            initialMedias: @js($dadosIniciaisGaleria['medias']),
            temMaisPaginas: @js($dadosIniciaisGaleria['temMais']),
            allowMultiple: @js($allowMultiple),
            maxItems: @js($maxItems),
            aspectRatios: @js($imageEditorAspectRatios ?? []),
            translations: @js($translations)
        })"
        x-init="init()"
    >

        {{-- Mídias Selecionadas --}}
        <div x-show="selecionadas.length > 0" class="g-section">
            <label class="g-label">
                <span x-text="mediaType === 'image' ? '📸 {{ __('filament-media-gallery::filament-media-gallery.labels.selected_images') }}' : '🎬 {{ __('filament-media-gallery::filament-media-gallery.labels.selected_videos') }}'"></span>
            </label>
            <div class="g-grid">
                {{-- Renderiza as mídias disponíveis e o Alpine controla a visibilidade --}}
                <template x-for="media in mediasDisponiveis" :key="media.id">
                    <div x-show="isSelected(media.id)" class="g-thumbnail g-thumbnail-selected group">
                        {{-- Preview de Imagem --}}
                        <template x-if="mediaType === 'image' && !media.is_video">
                            <img :src="media.url" :alt="media.nome_original">
                        </template>
                        {{-- Preview de Vídeo (usa thumbnail se disponível) --}}
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
                                        @click.stop="abrirEditor(media.id, media.url)"
                                        title="{{ __('filament-media-gallery::filament-media-gallery.buttons.edit_image') }}"
                                        class="g-thumbnail-btn-edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            @endif

                            <button type="button"
                                    @click.stop="removerMedia(media.id)"
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

        {{-- Arquivos Carregados para Upload --}}
        @if($allowUpload)
            <div x-show="uploadedFiles.length > 0" class="g-section">
                <label class="g-label">
                    📁 {{ __('filament-media-gallery::filament-media-gallery.labels.ready_for_upload') }}
                </label>
                <div class="g-grid">
                    <template x-for="(file, index) in uploadedFiles" :key="file.name">
                        <div class="g-thumbnail g-thumbnail-upload group">
                            {{-- Preview condicional baseado no tipo --}}
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

        {{-- Área de Botões de Ação - SEPARADA --}}
        <div class="g-actions-container">
            <div class="g-actions-title">
                ⚡ {{ __('filament-media-gallery::filament-media-gallery.labels.available_actions') }}
            </div>

            {{-- INDICADOR DE PROGRESSO DO UPLOAD --}}
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
                        @click="modalAberto = true"
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

        {{-- Modal da Galeria --}}
        <div x-show="modalAberto"
             x-cloak
             class="g-modal-overlay"
             style="display: none;">
            <div class="g-modal-container">
                <div @click.away="modalAberto = false" class="g-modal-content">
                    <div class="g-modal-header">
                        <h3 class="g-modal-title">
                            <span x-text="mediaType === 'image' ? '{{ __('filament-media-gallery::filament-media-gallery.labels.image_gallery') }}' : '{{ __('filament-media-gallery::filament-media-gallery.labels.video_gallery') }}'"></span>
                        </h3>
                        <button type="button" @click="modalAberto = false" class="g-modal-close-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="g-modal-grid">
                        <template x-if="mediasDisponiveis.length > 0">
                            <template x-for="media in mediasDisponiveis" :key="media.id">
                                <div @click="toggleMedia(media.id)"
                                     :class="{ 'g-modal-thumb-selected': isSelected(media.id) }"
                                     class="g-modal-thumb">
                                    {{-- Exibe imagem OU vídeo baseado no mediaType do campo --}}
                                    <template x-if="mediaType === 'image' && !media.is_video">
                                        <img :src="media.url" :alt="media.nome_original" class="g-modal-thumb-img">
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
                        <template x-if="mediasDisponiveis.length === 0">
                            <div class="g-modal-empty">
                                <span x-text="mediaType === 'image' ? '{{ __('filament-media-gallery::filament-media-gallery.empty.no_images') }}' : '{{ __('filament-media-gallery::filament-media-gallery.empty.no_videos') }}'"></span>
                            </div>
                        </template>
                    </div>

                    <div class="g-modal-footer">
                        <button type="button"
                                @click="modalAberto = false"
                                class="g-btn g-btn-primary">
                            {{ __('filament-media-gallery::filament-media-gallery.buttons.confirm_selection') }}
                        </button>
                        <button type="button"
                                x-show="temMaisPaginas"
                                @click="carregarMais()"
                                :disabled="carregandoMais"
                                class="g-btn g-btn-secondary"
                                x-text="carregandoMais ? '{{ __('filament-media-gallery::filament-media-gallery.buttons.loading') }}' : '{{ __('filament-media-gallery::filament-media-gallery.buttons.load_more') }}'">
                            {{ __('filament-media-gallery::filament-media-gallery.buttons.load_more') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal do Editor de Imagem (APENAS PARA IMAGENS) --}}
        @if($allowImageEditor)
            <div x-show="editorAberto" x-cloak class="g-modal-overlay" style="display: none;">
                <div class="g-modal-container">
                    <div @click.away="fecharEditor()" class="g-modal-content g-modal-editor">

                        <div class="g-modal-header">
                            <h3 class="g-modal-title">{{ __('filament-media-gallery::filament-media-gallery.labels.image_editor') }}</h3>
                            <button type="button" @click="fecharEditor()" class="g-modal-close-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Toolbar do Editor -->
                        <div class="g-editor-toolbar">
                            <div class="g-toolbar-group">
                                <button type="button" @click="resetarImagem()" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.reset') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="g-toolbar-group">
                                <button type="button" @click="rotacionar(-90)" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.rotate_left') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="rotacionar(90)" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.rotate_right') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="g-toolbar-group">
                                <button type="button" @click="espelharHorizontal()" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.flip_horizontal') }}">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </button>
                                <button type="button" @click="espelharVertical()" class="g-toolbar-btn" title="{{ __('filament-media-gallery::filament-media-gallery.editor.flip_vertical') }}">
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
                                    <select @change="mudarAspectRatio($event.target.value)" class="g-toolbar-select">
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
                                    @click="fecharEditor()"
                                    class="g-btn g-btn-secondary">
                                {{ __('filament-media-gallery::filament-media-gallery.buttons.cancel') }}
                            </button>
                            <button type="button"
                                    @click="salvarImagemEditada()"
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

    .g-modal-video {
        /* Se houver estilos específicos para .g-modal-video, eles viriam aqui */
    }
</style>
<script>
    function imageGalleryPicker(cfg) {
        return {
            selecionadas: cfg.state || [],
            mediasDisponiveis: cfg.initialMedias || [],
            modalAberto: false,
            mediaType: cfg.mediaType,
            uploadedFiles: [],
            editorAberto: false,
            cropper: null,
            imagemParaEditarId: null,
            imagemParaEditarUrl: null,
            arquivoParaEditar: null,
            aspectRatios: cfg.aspectRatios || [],
            currentAspectRatio: (cfg.aspectRatios && cfg.aspectRatios.length > 0) ? cfg.aspectRatios[0] : 'free',
            uploading: false,
            uploadProgress: '',
            paginaAtual: 1,
            temMaisPaginas: cfg.temMaisPaginas || false,
            carregandoMais: false,
            statePath: cfg.statePath,
            allowMultiple: cfg.allowMultiple,
            maxItems: cfg.maxItems,

            init() {
                console.log('🖼️ Galeria Iniciada:', this.mediaType);

                this.$watch('selecionadas', value => {
                    this.$wire.set(this.statePath, value);
                });

                Livewire.on('galeria:media-adicionada', ({media}) => {
                    if (media.is_video === (this.mediaType === 'video')) {
                        if (!this.mediasDisponiveis.some(m => m.id === media.id)) {
                            this.mediasDisponiveis.unshift(media);
                        }
                    }
                });
            },

            isSelected(mediaId) {
                return this.selecionadas.map(id => parseInt(id)).includes(parseInt(mediaId));
            },

            toggleMedia(mediaId) {
                if (this.allowMultiple) {
                    const index = this.selecionadas.indexOf(mediaId);
                    if (index > -1) {
                        this.selecionadas.splice(index, 1);
                    } else {
                        if (this.maxItems && this.selecionadas.length >= this.maxItems) {
                            return;
                        }
                        this.selecionadas.push(mediaId);
                    }
                } else {
                    this.selecionadas = this.isSelected(mediaId) ? [] : [mediaId];
                }
            },

            removerMedia(mediaId) {
                const index = this.selecionadas.indexOf(mediaId);
                if (index > -1) {
                    this.selecionadas.splice(index, 1);
                }
            },

            handleMediaUpload(event) {
                const file = event.target.files[0];
                if (!file) return;

                if (!this.allowMultiple && this.selecionadas.length > 0) {
                    alert('Apenas um item permitido');
                    event.target.value = '';
                    return;
                }

                this.uploading = true;
                this.uploadProgress = `Enviando ${file.name}...`;

                this.$wire.upload(
                    this.statePath + '_new_media',
                    file,
                    () => {
                        this.$wire.call('handleNewMediaUpload', file.name, this.statePath)
                            .then(() => {
                                this.uploading = false;
                                this.uploadProgress = '';
                                event.target.value = '';
                            })
                            .catch(() => {
                                this.uploading = false;
                                this.uploadProgress = '';
                                event.target.value = '';
                            });
                    },
                    () => {
                        this.uploading = false;
                        this.uploadProgress = '';
                        event.target.value = '';
                    },
                    (evt) => {
                        this.uploadProgress = `Enviando: ${Math.round(evt.detail.progress)}%`;
                    }
                );
            },

            carregarMais() {
                if (this.carregandoMais || !this.temMaisPaginas) return;
                this.carregandoMais = true;
                this.paginaAtual++;

                this.$wire.call('carregarMaisMedias', this.paginaAtual, this.statePath)
                    .then(resultado => {
                        this.mediasDisponiveis.push(...resultado.medias);
                        this.temMaisPaginas = resultado.temMais;
                        this.carregandoMais = false;
                    })
                    .catch(() => {
                        this.carregandoMais = false;
                    });
            },

            async abrirEditor(imagemId, imagemUrl) {
                if (this.mediaType !== 'image') return;

                this.imagemParaEditarId = imagemId;
                this.imagemParaEditarUrl = imagemUrl;

                try {
                    const response = await fetch(imagemUrl);
                    const blob = await response.blob();
                    const file = new File([blob], imagemUrl.split('/').pop(), {type: blob.type});
                    this.arquivoParaEditar = file;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.$refs.imageEditorCanvas.src = e.target.result;
                        this.editorAberto = true;
                        this.$nextTick(() => this.initCropper());
                    };
                    reader.readAsDataURL(file);
                } catch (error) {
                    console.error('Erro ao carregar imagem:', error);
                }
            },

            fecharEditor() {
                this.editorAberto = false;
                if (this.cropper) {
                    this.cropper.destroy();
                    this.cropper = null;
                }
                this.$refs.imageEditorCanvas.src = '';
                this.imagemParaEditarId = null;
                this.imagemParaEditarUrl = null;
                this.arquivoParaEditar = null;
            },

            initCropper() {
                if (this.cropper) this.cropper.destroy();
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
                    toggleDragModeOnDblclick: false
                });
            },

            getAspectRatioValue(ratioString) {
                if (!ratioString || ratioString === 'free') return NaN;
                const parts = ratioString.split(':');
                return parseFloat(parts[0]) / parseFloat(parts[1]);
            },

            resetarImagem() { if (this.cropper) this.cropper.reset(); },
            rotacionar(degree) { if (this.cropper) this.cropper.rotate(degree); },
            espelharHorizontal() { if (this.cropper) this.cropper.scaleX(-this.cropper.getData().scaleX || -1); },
            espelharVertical() { if (this.cropper) this.cropper.scaleY(-this.cropper.getData().scaleY || -1); },
            zoom(factor) { if (this.cropper) this.cropper.zoom(factor); },
            mudarAspectRatio(ratioString) {
                this.currentAspectRatio = ratioString;
                if (this.cropper) this.cropper.setAspectRatio(this.getAspectRatioValue(ratioString));
            },

            salvarImagemEditada() {
                if (!this.cropper) return;

                this.cropper.getCroppedCanvas().toBlob((blob) => {
                    const fileName = `${this.arquivoParaEditar.name.split('.').slice(0, -1).join('.')}_edited.png`;

                    this.$wire.upload(this.statePath + '_edited_media', blob, () => {
                        this.$wire.call('handleEditedMediaUpload', this.imagemParaEditarId, fileName, this.statePath)
                            .then(() => {
                                this.fecharEditor();
                                this.$wire.$refresh();
                            });
                    });
                }, 'image/png');
            }
        }
    }
</script>
