<?php

namespace Src\Content\Application\Actions;

use App\Helpers\TextModerationHelper;
use Illuminate\Support\Facades\RateLimiter;
use Jenssegers\Agent\Agent;
use Src\Content\Domain\Models\Article;

class StoreCommentAction
{
    public function execute(string $articleSlug, array $data, ?int $userId = null): array
    {
        // Bot Detection
        if ($this->isBotRequest()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json(['message' => 'Ação não permitida.'], 403));
        }

        $userId = $userId ?? auth()->id();

        // Rate Limiting
        $this->checkRateLimit($userId);

        $article = Article::where('slug', $articleSlug)->firstOrFail();

        // Moderation
        $moderation = TextModerationHelper::analyze($data['content']);

        if (! $moderation['approved'] && $moderation['reason'] === 'offensive_content') {
            throw new \DomainException($moderation['message']);
        }

        $isApproved = $moderation['approved'];

        $comment = $article->comments()->create([
            'user_id' => $userId,
            'parent_id' => $data['parent_id'] ?? null,
            'content' => strip_tags($data['content']), // Enforce plain text
            'is_approved' => $isApproved,
        ]);

        // Dispatch Event for Notifications
        \Src\Content\Application\Events\CommentCreated::dispatch($comment);

        return [
            'comment' => $comment,
            'moderation' => $moderation,
            'is_approved' => $isApproved,
        ];
    }

    private function checkRateLimit($userId): void
    {
        $key = 'comments:user:'.($userId ?? request()->ip());

        $executed = RateLimiter::attempt(
            $key,
            1, // max attempts
            function () {
                return true;
            },
            3600 // seconds (60 minutes)
        );

        if (! $executed) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                "Você está comentando muito rápido. Por favor, aguarde {$minutes} minutos."
            );
        }
    }

    private function isBotRequest(): bool
    {
        $userAgent = (string) request()->userAgent();

        if ($userAgent === '') {
            return false;
        }

        $agent = new Agent;

        return $agent->isRobot($userAgent);
    }
}
