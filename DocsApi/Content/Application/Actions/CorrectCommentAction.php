<?php

namespace Src\Content\Application\Actions;

use Src\Content\Domain\Models\Comment;
use Src\Content\Domain\Services\CommentCorrectionService;

class CorrectCommentAction
{
    public function __construct(
        private CommentCorrectionService $correctionService
    ) {}

    public function execute(int $commentId): array
    {
        $comment = Comment::findOrFail($commentId);

        $originalContent = $comment->getRawOriginal('content');
        $correctedContent = $this->correctionService->correct($originalContent);

        $hasChanges = $originalContent !== $correctedContent;

        // Sempre marca como corrigido, mesmo sem alterações
        $updateData = ['ai_corrected_at' => now()];

        if ($hasChanges) {
            $updateData['content'] = $correctedContent;
        }

        $comment->update($updateData);

        return [
            'original' => $originalContent,
            'corrected' => $correctedContent,
            'has_changes' => $hasChanges,
        ];
    }
}
