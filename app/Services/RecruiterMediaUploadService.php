<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecruiterMediaUploadService
{
    public function upload(UploadedFile $file): array
    {
        $mime = $file->getMimeType() ?? '';

        if (str_starts_with($mime, 'video/')) {
            return $this->processVideo($file);
        }

        return $this->processImage($file);
    }

    private function processImage(UploadedFile $file): array
    {
        $path = $file->store(
            'recruiters/posts',
            'public'
        );

        return [
            'file_path' => $path,
            'type' => 'image',
        ];
    }

    private function processVideo(UploadedFile $file): array
    {
        $filename = 'recruiters/posts/'.Str::uuid().'.mp4';

        $inputPath = $file->getRealPath();

        $outputPath = storage_path(
            'app/public/'.$filename
        );

        Storage::disk('public')->makeDirectory(dirname($filename));

        $command = sprintf(
            '"%s" -i %s -vf "scale=\'min(1280,iw)\':-2" -c:v libx264 -preset veryfast -crf 28 -c:a aac -b:a 128k -movflags +faststart %s -y 2>&1',
            env('FFMPEG_BINARY', 'C:\ffmpeg\bin\ffmpeg.exe'),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        $output = [];
        $status = 0;

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
