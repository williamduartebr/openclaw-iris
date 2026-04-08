<?php

namespace Src\Content\Application\Services;

use Illuminate\Http\JsonResponse;
use Src\Content\Domain\Exceptions\InvalidMediaReferenceException;
use Src\Content\Domain\Exceptions\InvalidStatusTransitionException;
use Src\Content\Domain\Exceptions\VersionConflictException;
use Src\Content\Domain\Models\Article;

class ContentApiResponseService
{
    public function articleNotFound(?string $slug = null): JsonResponse
    {
        if ($slug !== null) {
            return response()->json([
                'message' => 'Article not found.',
                'slug' => $slug,
            ], 404);
        }

        return response()->json(['message' => 'Article not found.'], 404);
    }

    public function articleNotFoundOrNotDeleted(): JsonResponse
    {
        return response()->json(['message' => 'Article not found or not currently deleted.'], 404);
    }

    public function invalidMediaReference(InvalidMediaReferenceException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => ['cover_media_id' => [$exception->getMessage()]],
        ], 422);
    }

    public function versionConflict(VersionConflictException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'current_version' => $exception->currentVersion,
            'your_version' => $exception->providedVersion,
        ], 409);
    }

    public function invalidStatusTransition(InvalidStatusTransitionException $exception): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid status transition.',
            'errors' => ['status' => [$exception->getMessage()]],
        ], 422);
    }

    public function articleSoftDeleted(Article $article): JsonResponse
    {
        return response()->json([
            'message' => 'Article soft-deleted successfully.',
            'id' => $article->id,
            'deleted_at' => $article->deleted_at->toIso8601String(),
        ]);
    }

    public function schedulePublishedAtRequired(): JsonResponse
    {
        return response()->json([
            'message' => 'The published_at field is required for scheduling.',
            'errors' => ['published_at' => ['The published_at field is required.']],
        ], 422);
    }

    public function schedulePublishedAtFuture(): JsonResponse
    {
        return response()->json([
            'message' => 'The published_at must be a future date.',
            'errors' => ['published_at' => ['The published_at must be a future date.']],
        ], 422);
    }
}
