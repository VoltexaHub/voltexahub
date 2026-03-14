<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

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
        $this->validateMagicBytes($file);

        // GIFs: re-encode frame 0 through GD to strip metadata and embedded data.
        if (in_array(strtolower($file->getClientOriginalExtension()), ['gif'])
            || $file->getMimeType() === 'image/gif') {
            $gdImage = @imagecreatefromgif($file->getRealPath());
            if (!$gdImage) {
                throw new \InvalidArgumentException('Invalid GIF file.');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'gif_');
            try {
                imagegif($gdImage, $tempPath);
                imagedestroy($gdImage);

                $filename = Str::random(40) . '.gif';
                $path = $directory . '/' . $filename;
                Storage::disk('public')->put($path, file_get_contents($tempPath));

                return $path;
            } finally {
                @unlink($tempPath);
            }
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

    private function validateMagicBytes(UploadedFile $file): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                'File is not a valid image (detected: ' . $detectedMime . ').'
            );
        }
    }
}
