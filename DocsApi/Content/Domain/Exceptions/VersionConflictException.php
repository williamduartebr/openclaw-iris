<?php

namespace Src\Content\Domain\Exceptions;

use DomainException;

class VersionConflictException extends DomainException
{
    public function __construct(
        public readonly int $currentVersion,
        public readonly int $providedVersion,
    ) {
        parent::__construct(
            'Version conflict. The article has been modified since your last read.'
        );
    }
}
