<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;

class OptimizedImageUploadService
{
    /**
     * MIME types that can be safely re-encoded by the image driver.
     * SVG is not rasterizable and GIF would lose animation, so both
     * are stored as-is.
     *
     * @var list<string>
     */
    private const PROCESSABLE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Store an upload on the public disk, re-encoding supported
     * raster images to WebP via Laravel's first-party image API.
     */
    public function store(UploadedFile $file, string $folder): string
    {
        if (! in_array($file->getMimeType(), self::PROCESSABLE_MIMES, true)) {
            return $file->store($folder, 'public');
        }

        return Image::fromUpload($file)
            ->optimize()
            ->storePublicly($folder, 'public');
    }
}
