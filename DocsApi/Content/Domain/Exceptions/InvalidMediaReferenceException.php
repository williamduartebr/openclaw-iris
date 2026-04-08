<?php

namespace Src\Content\Domain\Exceptions;

use DomainException;

class InvalidMediaReferenceException extends DomainException
{
    public function __construct(
        public readonly int $mediaId,
        string $reason = 'not found',
    ) {
        parent::__construct(
            "Invalid media reference: media_id {$mediaId} {$reason}."
        );
    }
}
