<?php

namespace Src\Content\Application\Actions;

use Illuminate\Support\Str;
use Src\Content\Application\Events\NewsletterSubscribed;
use Src\Content\Domain\Models\NewsletterSubscriber;

class StoreNewsletterAction
{
    public function execute(string $email, ?string $name = null, ?string $categorySlug = null, ?string $sourceUrl = null): array
    {
        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing && $existing->is_active && $existing->email_verified_at) {
            return [
                'subscriber' => $existing,
                'code' => null,
                'status' => 'already_subscribed',
            ];
        }

        $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($existing) {
            $existing->update([
                'name' => $name ?? $existing->name,
                'verification_code' => $code,
                'category_slug' => $categorySlug ?? $existing->category_slug,
                'source_url' => $sourceUrl ?? $existing->source_url,
                'lgpd_consent_at' => now(),
                'unsubscribe_token' => $existing->unsubscribe_token ?? Str::random(64),
            ]);

            $subscriber = $existing->fresh();
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'name' => $name,
                'is_active' => false,
                'verification_code' => $code,
                'category_slug' => $categorySlug,
                'source_url' => $sourceUrl,
                'lgpd_consent_at' => now(),
                'unsubscribe_token' => Str::random(64),
            ]);
        }

        event(new NewsletterSubscribed($subscriber, $code));

        return [
            'subscriber' => $subscriber,
            'code' => $code,
            'status' => $existing ? 'resubscribed' : 'created',
        ];
    }
}
