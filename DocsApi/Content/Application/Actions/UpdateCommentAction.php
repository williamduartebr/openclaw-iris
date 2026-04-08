<?php

namespace Src\Content\Application\Actions;

use App\Helpers\TextModerationHelper;
use Illuminate\Validation\ValidationException;
use Src\Content\Domain\Models\Comment;

class UpdateCommentAction
{
    public function execute(int $commentId, array $data, int $userId): array
    {
        $comment = Comment::findOrFail($commentId);

        // Authorization
        if ($comment->user_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Não autorizado.');
        }

        // Time Window Check
        if ($comment->created_at->diffInMinutes(now()) > 5) {
            throw ValidationException::withMessages(['message' => 'O tempo para editar este comentário expirou.']);
        }

        // Moderation
        $moderation = TextModerationHelper::analyze($data['content']);

        if (! $moderation['approved'] && $moderation['reason'] === 'offensive_content') {
            throw ValidationException::withMessages(['content' => $moderation['message']]);
        }

        $isApproved = $moderation['approved'];

        $comment->update([
            'content' => strip_tags($data['content']),
            'is_approved' => $isApproved,
            'ai_corrected_at' => null, // Reseta correção ao editar
        ]);

        return [
            'comment' => $comment,
            'moderation' => $moderation,
            'is_approved' => $isApproved,
        ];
    }
}
