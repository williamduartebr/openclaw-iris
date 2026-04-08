<?php

namespace Src\Media\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Src\Media\Application\Services\MediaGenerationService;
use Src\Media\Domain\Enums\MediaStatus;
use Src\Media\Domain\Models\MediaAsset;

class GenerateMediaImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public readonly int $mediaAssetId,
    ) {
        $this->onQueue(config('media.queue', 'default'));
    }

    public function handle(MediaGenerationService $generationService): void
    {
        $generationService->processGeneration($this->mediaAssetId);
    }

    public function failed(\Throwable $exception): void
    {
        $asset = MediaAsset::find($this->mediaAssetId);

        if ($asset && $asset->status !== MediaStatus::Failed) {
            $asset->update([
                'status' => MediaStatus::Failed,
                'failure_reason' => $exception->getMessage(),
            ]);
        }

        Log::error('GenerateMediaImage job failed', [
            'asset_id' => $this->mediaAssetId,
            'error' => $exception->getMessage(),
        ]);
    }
}
