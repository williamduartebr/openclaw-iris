<?php

namespace Src\Content\Application\Actions;

use Illuminate\Validation\ValidationException;
use Src\Content\Domain\Models\Comment;

class DeleteCommentAction
{
    public function execute(int $commentId, ?int $userId, bool $force = false): void
    {
        $comment = Comment::find($commentId);

        if (! $comment) {
            // Already deleted or doesn't exist, treat as success
            return;
        }

        // Authorization - ByPass if $force is true (Admin)
        if (! $force && $comment->user_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Não autorizado.');
        }

        // Time Window Check - ByPass if $force is true
        if (! $force && $comment->created_at->diffInMinutes(now()) > 5) {
            throw ValidationException::withMessages(['message' => 'O tempo para excluir este comentário expirou.']);
        }

        $comment->delete();
    }
}
