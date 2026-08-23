<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;

class MediaUploadService
{
    public function upload(UploadedFile $file): array
    {
        $mime = $file->getMimeType();

        if (str_contains($mime, 'video')) {
            return $this->processVideo($file);
        }

        return $this->processImage($file);
    }

    private function processImage(UploadedFile $file): array
    {
        $path = Image::fromUpload($file)
            ->scale(width: 1920)
            ->optimize(format: 'jpg', quality: 80)
            ->storePubliclyAs('posts', Str::uuid().'.jpg', 'public');

        return [
            'file_path' => $path,
            'type' => 'image',
        ];
    }

    private function processVideo(UploadedFile $file): array
    {
        $filename = 'posts/'.Str::uuid().'.mp4';

        $inputPath = $file->getRealPath();

        $outputPath = storage_path(
            'app/public/'.$filename
        );

        $command = sprintf(
            "ffmpeg -i %s -vf \"scale='min(1280,iw)':-2\" -c:v libx264 -preset medium -crf 28 -c:a aac -b:a 128k -movflags +faststart %s -y 2>&1",
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $status);

        if ($status !== 0) {
            throw new \Exception(
                'Video compression failed: '.implode("\n", $output)
            );
        }

        return [
            'file_path' => $filename,
            'type' => 'video',
        ];
    }
}
