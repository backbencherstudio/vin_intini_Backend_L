<?php

namespace App\Services\Industry;

use App\Services\Media\OptimizedImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IndustryMediaUploadService
{
    public function __construct(
        private readonly OptimizedImageUploadService $imageUploadService
    ) {}

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
        $path = $this->imageUploadService->store(
            $file,
            'industries/posts'
        );

        return [
            'file_path' => $path,
            'type' => 'image',
        ];
    }

    private function processVideo(UploadedFile $file): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $filename = 'industries/posts/'.Str::uuid().'.mp4';
        $inputPath = $file->getRealPath();
        $outputPath = storage_path('app/public/'.$filename);

        Storage::disk('public')->makeDirectory(dirname($filename));

        $ffmpegBinary = config('services.ffmpeg.binary', 'ffmpeg');

        try {
            // Step 1: Ultra-fast stream copy with +faststart (0.2s - 0.5s)
            $fastCommand = sprintf(
                '%s -i %s -c copy -movflags +faststart %s -y 2>&1',
                escapeshellcmd($ffmpegBinary),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            $output = [];
            $status = 0;
            exec($fastCommand, $output, $status);

            if ($status === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
                return [
                    'file_path' => $filename,
                    'type' => 'video',
                ];
            }

            // Step 2: Transcode with veryfast preset
            $transcodeCommand = sprintf(
                '%s -i %s -vf "scale=\'min(1280,iw)\':-2" -c:v libx264 -preset veryfast -crf 28 -c:a aac -b:a 128k -movflags +faststart %s -y 2>&1',
                escapeshellcmd($ffmpegBinary),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            $output = [];
            $status = 0;
            exec($transcodeCommand, $output, $status);

            if ($status === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
                return [
                    'file_path' => $filename,
                    'type' => 'video',
                ];
            }

            if (Storage::disk('public')->exists($filename)) {
                Storage::disk('public')->delete($filename);
            }

            \Illuminate\Support\Facades\Log::warning('Industry video compression failed, storing original file.', [
                'error' => implode("\n", $output),
            ]);
        } catch (\Throwable $e) {
            if (Storage::disk('public')->exists($filename)) {
                Storage::disk('public')->delete($filename);
            }

            \Illuminate\Support\Facades\Log::error('Industry video processing exception, falling back to original file.', [
                'message' => $e->getMessage(),
            ]);
        }

        $extension = $file->getClientOriginalExtension() ?: 'mp4';
        $fallbackFilename = Str::uuid().'.'.$extension;
        $path = $file->storePubliclyAs('industries/posts', $fallbackFilename, 'public');

        return [
            'file_path' => $path,
            'type' => 'video',
        ];
    }
}
