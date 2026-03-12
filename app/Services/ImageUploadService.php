<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadService
{
    /**
     * Resize and save an uploaded image.
     *
     * @param  UploadedFile  $file
     * @param  string  $directory   Storage disk path (e.g. 'avatars', 'covers')
     * @param  int  $width          Max width in px
     * @param  int  $height         Max height in px (null = maintain aspect ratio)
     * @param  int  $quality        JPEG/WebP quality (1–100)
     * @param  bool  $cover         true = crop to exact dimensions, false = fit within
     * @return string               Stored path (relative to public disk)
     */
    public function store(
        UploadedFile $file,
        string $directory,
        int $width,
        int $height = null,
        int $quality = 85,
        bool $cover = false
    ): string {
        // GIFs (especially animated) don't survive WebP conversion via GD.
        // Validate the GIF is a real image before storing directly to catch polyglot files.
        if (in_array(strtolower($file->getClientOriginalExtension()), ['gif'])
            || $file->getMimeType() === 'image/gif') {
            $imageInfo = @getimagesize($file->getRealPath());
            if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_GIF) {
                throw new \InvalidArgumentException('Invalid GIF file.');
            }
            return $file->storeAs($directory, Str::random(40) . '.gif', 'public');
        }

        $filename = Str::random(40) . '.webp';
        $path = $directory . '/' . $filename;

        $img = Image::read($file);

        if ($height && $cover) {
            $img->cover($width, $height);
        } elseif ($height) {
            $img->scaleDown($width, $height);
        } else {
            $img->scaleDown($width);
        }

        $encoded = $img->toWebp($quality);

        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
