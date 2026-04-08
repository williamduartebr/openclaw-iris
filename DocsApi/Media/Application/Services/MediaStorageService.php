<?php

namespace Src\Media\Application\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    public function uploadOriginal(int $assetId, string $imageData, string $mimeType): string
    {
        $extension = $this->extensionFromMime($mimeType);
        $filename = Str::uuid()->toString().'.'.$extension;
        $tempPrefix = config('media.storage.temp_prefix', 'temp/Media');
        $path = "{$tempPrefix}/{$assetId}/{$filename}";

        Storage::disk($this->disk())->put($path, $imageData, ['visibility' => 'private']);

        return $path;
    }

    public function buildProcessedPath(int $assetId, string $originalPath): string
    {
        $finalPrefix = config('media.storage.final_prefix', 'Media');
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

        return "{$finalPrefix}/{$assetId}/{$baseName}.webp";
    }

    public function getUrl(string $path): string
    {
        return Storage::disk($this->disk())->url($path);
    }

    public function getTemporaryUrl(string $path, int $minutes = 30): string
    {
        return Storage::disk($this->disk())->temporaryUrl($path, now()->addMinutes($minutes));
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk())->exists($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk())->delete($path);
    }

    private function disk(): string
    {
        return config('media.storage.disk', 's3');
    }

    private function extensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };
    }
}
