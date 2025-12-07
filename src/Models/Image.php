<?php

namespace Devanderson\FilamentMediaGallery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'images';

    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size',
        'alt'

    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = ['url'];

    /**
     * Retorna a URL completa da imagem.
     */
    public function getUrlAttribute(): string
    {
        $disk = config('filament-media-gallery.disk', 'public');
        return Storage::disk($disk)->url($this->path);
    }

    /**
     * Retorna o size formatado.
     */
    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Deleta a imagem do storage ao deletar o registro.
     */
    protected static function booted(): void
    {
        static::deleting(function (Image $imagem) {
            // Garante que o arquivo seja excluído apenas na exclusão forçada (forceDelete)
            if ($imagem->isForceDeleting()) {
                $disk = config('filament-media-gallery.disk', 'public');

                if (Storage::disk($disk)->exists($imagem->path)) {
                    Storage::disk($disk)->delete($imagem->path);
                }
            }
        });
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
