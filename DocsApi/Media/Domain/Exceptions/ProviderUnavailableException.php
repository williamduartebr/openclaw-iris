<?php

namespace Src\Media\Domain\Exceptions;

use RuntimeException;

class ProviderUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reason = 'Provider is not configured or unavailable',
    ) {
        parent::__construct("Provider unavailable: {$provider}. {$reason}");
    }
}
