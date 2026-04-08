<?php

namespace Src\Media\Domain\Exceptions;

use RuntimeException;
use Src\Media\Domain\Enums\MediaStatus;

class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(
        public readonly MediaStatus $currentStatus,
        public readonly MediaStatus $targetStatus,
    ) {
        parent::__construct(
            "Invalid status transition from '{$currentStatus->value}' to '{$targetStatus->value}'."
        );
    }
}
