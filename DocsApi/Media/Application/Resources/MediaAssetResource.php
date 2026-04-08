<?php

namespace Src\Media\Application\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt' => $this->prompt,
            'negative_prompt' => $this->negative_prompt,
            'width' => $this->width,
            'height' => $this->height,
            'style' => $this->style,
            'quality' => $this->quality,
            'original_path' => $this->original_path,
            'processed_path' => $this->processed_path,
            'final_url' => $this->final_url,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'original_size' => $this->original_size,
            'compression_ratio' => $this->compression_ratio,
            'metadata' => $this->metadata,
            'orchestrator_context' => $this->orchestrator_context,
            'provider_metadata' => $this->provider_metadata,
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
