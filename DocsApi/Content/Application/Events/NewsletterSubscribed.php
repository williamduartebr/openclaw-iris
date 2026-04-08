<?php

namespace Src\Content\Application\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Src\Content\Domain\Models\NewsletterSubscriber;

class NewsletterSubscribed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $code,
    ) {}
}
