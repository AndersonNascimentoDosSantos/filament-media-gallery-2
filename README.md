# Filament Media Gallery

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devanderson/filament-media-gallery.svg?style=flat-square)](https://packagist.org/packages/devanderson/filament-media-gallery)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/AndersonNascimentoDosSantos/filament-media-gallery-2/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/AndersonNascimentoDosSantos/filament-media-gallery-2/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/devanderson/filament-media-gallery.svg?style=flat-square)](https://packagist.org/packages/devanderson/filament-media-gallery)

A complete media gallery plugin for Filament v4 with support for image and video uploads, integrated image editor, automatic video thumbnail generation, and much more.

## 🌟 Features

- ✅ **Image Uploads** - Support for JPG, PNG, WebP, GIF
- ✅ **Video Uploads** - Support for MP4, WebM, OGG
- ✅ **Image Editor** - Integrated editor with Cropper.js
- ✅ **Video Thumbnails** - Automatic generation using FFmpeg
- ✅ **Paginated Gallery** - Intuitive and responsive interface
- ✅ **Dark Mode** - Complete support for dark mode
- ✅ **Multiple Selections** - Select one or multiple media items
- ✅ **Fully Configurable** - Customize everything via config file
- ✅ **Internationalization** - Support for multiple languages

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 11.0 or higher
- Filament 4.0 or higher
- FFmpeg (optional, for video thumbnails)

## 📦 Installation

### 1. Install via Composer

```bash
composer require devanderson/filament-media-gallery
```

### 2. Publish Migrations

```bash
php artisan vendor:publish --tag="filament-media-gallery-migrations"
php artisan migrate
```

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag="filament-media-gallery-config"
```

## 🚀 Basic Usage

### Understanding the Component

The `GalleryMediaField` is a custom Filament form component designed to browse and select one or more media items (images or videos) from a pre-existing gallery. It stores the IDs of the selected media, which are then used to create a many-to-many relationship between your primary model and the media models (`Image`, `Video`) provided by the gallery package.

### Step 1: Add the Component to Your Form

Add `GalleryMediaField` to your Filament form schema. You must specify the `mediaType` and a name for the field that will hold the selected IDs.

```php
use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;

// In your Form schema
GalleryMediaField::make('videos_ids')
    ->mediaType('video')
    ->allowMultiple()
    ->columnSpanFull()
```

**Field Configuration:**

- **`make('videos_ids')`**: Defines the field name that will hold an array of selected video IDs. Use a different name like `images_ids` for images.
- **`mediaType('video')`**: Specifies the type of media to display in the gallery. Use `'image'` for images.
- **`allowMultiple()`**: Allows the user to select more than one item.

### Step 2: Create Pivot Tables

A many-to-many relationship requires a "pivot" table to link your model with the media model. You need one pivot table for each media type you want to associate.

#### Image Pivot Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_image_image', function (Blueprint $table) {
            // Foreign key to your primary model's table
            $table->foreignId('project_image_id')->constrained()->onDelete('cascade');
            // Foreign key to the package's images table
            $table->foreignId('image_id')->constrained('images')->onDelete('cascade');
            // Primary key to prevent duplicates
            $table->primary(['project_image_id', 'image_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_image_image');
    }
};
```

#### Video Pivot Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_image_video', function (Blueprint $table) {
            $table->foreignId('project_image_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->primary(['project_image_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_image_video');
    }
};
```

### Step 3: Define Model Relationships

In your primary model, define the `belongsToMany` relationship for each media type. You must specify the custom pivot table name as the second argument.

```php
<?php

namespace App\Models;

use Devanderson\FilamentMediaGallery\Models\Image;
use Devanderson\FilamentMediaGallery\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectImage extends Model
{
    protected $fillable = [
        'title',
        'description',
        // ... other fields
    ];

    /**
     * Defines the many-to-many relationship with the Image model.
     * The second argument is the name of our pivot table.
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Image::class, 'project_image_image');
    }

    /**
     * Defines the many-to-many relationship with the Video model.
     * The second argument is the name of our pivot table.
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'project_image_video');
    }
}
```

### Step 4: Integrate with Filament Resource Pages

To make the component work properly, you need to add logic to your Filament `CreateRecord` and `EditRecord` pages to handle saving and loading the relationship data.

#### Using the ProcessUploadGallery Trait

The package provides a convenient trait that handles all the synchronization logic:

**CreateRecord Page:**

```php
<?php

namespace App\Filament\Resources\ProjectImageResource\Pages;

use App\Filament\Resources\ProjectImageResource;
use Filament\Resources\Pages\CreateRecord;
use Devanderson\FilamentMediaGallery\Traits\ProcessUploadGallery;

class CreateProjectImage extends CreateRecord
{
    use ProcessUploadGallery;

    protected static string $resource = ProjectImageResource::class;
}
```

**EditRecord Page:**

```php
<?php

namespace App\Filament\Resources\ProjectImageResource\Pages;

use App\Filament\Resources\ProjectImageResource;
use Filament\Resources\Pages\EditRecord;
use Devanderson\FilamentMediaGallery\Traits\ProcessUploadGallery;

class EditProjectImage extends EditRecord
{
    use ProcessUploadGallery;

    protected static string $resource = ProjectImageResource::class;

    /**
     * Load existing relationship IDs into form fields before filling the form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load images IDs
        $data['images_ids'] = $this->record->images()->pluck('images.id')->toArray();
        
        // Load videos IDs
        $data['videos_ids'] = $this->record->videos()->pluck('videos.id')->toArray();

        return $data;
    }
}
```

**What the Trait Does:**

- **`afterCreate` hook**: Synchronizes the selected media IDs with the model relationships after creating a new record
- **`afterSave` hook**: Synchronizes the selected media IDs when editing an existing record

### Complete Form Example

```php
<?php

namespace App\Filament\Resources\ProjectImageResource;

use Filament\Forms;
use Filament\Forms\Form;
use Devanderson\FilamentMediaGallery\Forms\Components\GalleryMediaField;

class ProjectImageForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                // Gallery field for images
                GalleryMediaField::make('images_ids')
                    ->label('Gallery Images')
                    ->mediaType('image')
                    ->allowMultiple()
                    ->columnSpanFull(),

                // Gallery field for videos
                GalleryMediaField::make('videos_ids')
                    ->label('Gallery Videos')
                    ->mediaType('video')
                    ->allowMultiple()
                    ->columnSpanFull(),
            ]);
    }
}
```

## 📝 Advanced Usage Examples

### Single Image Selection (Avatar, Cover, etc)

```php
GalleryMediaField::make('avatar_id')
    ->label('Profile Picture')
    ->mediaType('image')
    ->allowMultiple(false)
    ->required()
```

### Limited Selection with Max Items

```php
GalleryMediaField::make('featured_images')
    ->label('Featured Images')
    ->mediaType('image')
    ->allowMultiple()
    ->maxItems(5)
```

### Video Gallery

```php
GalleryMediaField::make('videos_ids')
    ->label('Video Gallery')
    ->mediaType('video')
    ->allowMultiple()
    ->maxItems(10)
```

## ⚙️ Configuration

The `config/filament-media-gallery.php` file offers various options:

```php
return [
    // Storage disk
    'disk' => env('MEDIA_GALLERY_DISK', 'public'),

    // Storage path
    'path' => env('MEDIA_GALLERY_PATH', 'gallery'),

    // Image settings
    'image' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_size' => 10240, // KB
        'editor' => [
            'enabled' => true,
            'aspect_ratios' => ['16:9', '4:3', '1:1', '9:16'],
        ],
    ],

    // Video settings
    'video' => [
        'allowed_extensions' => ['mp4', 'webm', 'ogg'],
        'max_size' => 102400, // KB
        'thumbnail' => [
            'enabled' => true,
            'time' => 1.0,
            'width' => 640,
        ],
    ],

    // Gallery pagination
    'gallery' => [
        'per_page' => 24,
        'allow_multiple' => true,
        'max_items' => null,
    ],
];
```

## 🎬 FFmpeg Setup (Video Thumbnails)

To enable automatic thumbnail generation for videos, install FFmpeg:

### Ubuntu/Debian
```bash
sudo apt-get install ffmpeg
```

### macOS
```bash
brew install ffmpeg
```

### Windows
Download from: https://ffmpeg.org/download.html

### Configure in .env
```env
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe
```

## ⚠️ Important Notes

### Modal Forms Limitation

This implementation has been designed for use in standard Filament `CreateRecord` and `EditRecord` pages. It has **not** been tested within the context of modal forms (e.g., `createOptionForm` or `editOptionForm` inside a `Select` component). Using this component in modals may require additional adjustments to correctly handle the component's state and data flow.

### Relationship Synchronization

The `ProcessUploadGallery` trait automatically handles the synchronization of media relationships. It:

1. Extracts field names ending with `_ids` from the form data
2. Converts field names to relationship names (e.g., `videos_ids` → `videos`)
3. Syncs the selected IDs with the corresponding relationship
4. Removes the temporary `_ids` fields from the saved data

## 🌍 Internationalization

The plugin includes translations for:
- 🇧🇷 Portuguese (pt_BR)
- 🇺🇸 English (en)

To add new translations:

```bash
php artisan vendor:publish --tag="filament-media-gallery-translations"
```

## 🧪 Testing

```bash
composer test
```

## 📝 Changelog

See [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## 🤝 Contributing

Contributions are welcome! See [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## 🔒 Security

If you discover security issues, please email security@example.com.

## 📄 License

The MIT License (MIT). See [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Anderson Nascimento dos Santos](https://github.com/AndersonNascimentoDosSantos)
- [Filament](https://filamentphp.com)
- [Cropper.js](https://github.com/fengyuanchen/cropperjs)
- [FFmpeg](https://ffmpeg.org)
- [All Contributors](../../contributors)

## 💡 Support

- 📖 [Documentation](https://github.com/AndersonNascimentoDosSantos/filament-media-gallery-2)
- 🐛 [Issues](https://github.com/AndersonNascimentoDosSantos/filament-media-gallery-2/issues)
- 💬 [Discussions](https://github.com/AndersonNascimentoDosSantos/filament-media-gallery-2/discussions)
