<?php

namespace Src\Content\Application\Listeners;

use Illuminate\Support\Facades\Mail;
use Src\Content\Application\Events\NewsletterSubscribed;
use Src\Content\Application\Mail\NewsletterVerificationMail;

class SendNewsletterVerificationEmail
{
    public function handle(NewsletterSubscribed $event): void
    {
        Mail::to($event->subscriber->email)
            ->send(new NewsletterVerificationMail($event->subscriber, $event->code));
    }
}
