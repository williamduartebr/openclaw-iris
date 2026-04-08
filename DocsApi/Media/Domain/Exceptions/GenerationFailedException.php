<?php

namespace Src\Media\Domain\Exceptions;

use RuntimeException;

class GenerationFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("Image generation failed [{$provider}/{$model}]: {$reason}", 0, $previous);
    }
}
