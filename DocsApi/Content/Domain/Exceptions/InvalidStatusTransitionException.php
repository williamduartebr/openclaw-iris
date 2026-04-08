<?php

namespace Src\Content\Domain\Exceptions;

use DomainException;

class InvalidStatusTransitionException extends DomainException
{
    public function __construct(
        public readonly string $currentStatus,
        public readonly string $targetStatus,
        public readonly array $allowedTransitions,
    ) {
        $allowed = implode(', ', $this->allowedTransitions);

        parent::__construct(
            "Cannot transition from '{$this->currentStatus}' to '{$this->targetStatus}'. Allowed transitions: {$allowed}."
        );
    }
}
