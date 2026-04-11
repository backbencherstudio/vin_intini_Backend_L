<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileImageService
{
    private const DEFAULT_FOLDER = 'profile_photos';
    private const MAX_BYTES = 5_242_880; // 5MB

    public function storeUploaded(
        UploadedFile $file,
        ?string $existingPath = null,
        string $folder = self::DEFAULT_FOLDER
    ): string {
        $this->deleteIfLocal($existingPath);

        return $file->store($folder, 'public');
    }

    public function storeFromUrl(
        string $url,
        ?string $existingPath = null,
        string $folder = self::DEFAULT_FOLDER
    ): ?string {
        $url = trim($url);
        if ($url === '' || !preg_match('/^https?:\\/\\//i', $url)) {
            return null;
        }

        $response = Http::timeout(10)
            ->withOptions(['allow_redirects' => true])
            ->withHeaders(['Accept' => 'image/*'])
            ->get($url);

        if (!$response->successful()) {
            return null;
        }

        $contentType = (string) $response->header('Content-Type', '');
        if ($contentType === '' || !Str::startsWith(Str::lower($contentType), 'image/')) {
            return null;
        }

        $body = $response->body();
        if ($body === '') {
            return null;
        }

        if (strlen($body) > self::MAX_BYTES) {
            return null;
        }

        $extension = $this->guessExtensionFromContentType($contentType) ?? 'jpg';
        $filename = Str::uuid() . '.' . $extension;
        $path = trim($folder, '/') . '/' . $filename;

        Storage::disk('public')->put($path, $body);

        $this->deleteIfLocal($existingPath);

        return $path;
    }

    public function deleteIfLocal(?string $path): void
    {
        if (!$path || $this->isRemote($path)) {
            return;
        }

        $path = ltrim($path, '/');
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function isRemote(?string $value): bool
    {
        return is_string($value) && preg_match('/^https?:\\/\\//i', $value) === 1;
    }

    private function guessExtensionFromContentType(string $contentType): ?string
    {
        $contentType = Str::lower(trim(explode(';', $contentType)[0] ?? ''));

        return match ($contentType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => null,
        };
    }
}

