<?php

namespace Src\Media\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Src\Media\Domain\Enums\MediaStatus;
use Src\Media\Infrastructure\Database\Factories\MediaAssetFactory;

class MediaAsset extends Model
{
    use HasFactory;

    protected $table = 'media_assets';

    protected static function newFactory(): MediaAssetFactory
    {
        return MediaAssetFactory::new();
    }

    protected $fillable = [
        'status',
        'provider',
        'model',
        'prompt',
        'negative_prompt',
        'width',
        'height',
        'style',
        'quality',
        'original_path',
        'processed_path',
        'mime_type',
        'file_size',
        'original_size',
        'compression_ratio',
        'failure_reason',
        'metadata',
        'orchestrator_context',
        'provider_metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MediaStatus::class,
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'original_size' => 'integer',
            'compression_ratio' => 'float',
            'metadata' => 'array',
            'orchestrator_context' => 'array',
            'provider_metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function getFinalUrlAttribute(): ?string
    {
        if (! $this->processed_path) {
            return null;
        }

        return Storage::disk('s3')->url($this->processed_path);
    }

    public function isCompleted(): bool
    {
        return $this->status === MediaStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === MediaStatus::Failed;
    }

    public function isProcessing(): bool
    {
        return ! $this->status->isFinal();
    }
}
