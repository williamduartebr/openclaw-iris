<?php

namespace Src\Media\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Media\Domain\Enums\MediaStatus;
use Src\Media\Domain\Models\MediaAsset;

class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'status' => MediaStatus::Pending,
            'provider' => 'openai',
            'model' => 'gpt-image-1',
            'prompt' => $this->faker->sentence(10),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => MediaStatus::Completed,
            'original_path' => 'temp/Media/1/'.$this->faker->uuid().'.png',
            'processed_path' => 'Media/1/'.$this->faker->uuid().'.webp',
            'mime_type' => 'image/webp',
            'file_size' => $this->faker->numberBetween(50000, 200000),
            'original_size' => $this->faker->numberBetween(300000, 800000),
            'compression_ratio' => $this->faker->randomFloat(4, 0.1, 0.5),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => MediaStatus::Failed,
            'failure_reason' => 'API rate limit exceeded',
        ]);
    }
}
