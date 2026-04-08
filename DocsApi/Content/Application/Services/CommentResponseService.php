<?php

namespace Src\Content\Application\Services;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentResponseService
{
    public function normalizeArticleSlug(string $categorySlug, string $articleSlug): string
    {
        if ($articleSlug !== '') {
            return $articleSlug;
        }

        return $categorySlug;
    }

    public function storeSuccess(Request $request, array $result): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            $message = $result['is_approved']
                ? 'Comentário enviado com sucesso!'
                : $result['moderation']['message'];

            return response()->json([
                'success' => true,
                'message' => $message,
                'comment' => $result['comment']->load('user'),
                'is_approved' => $result['is_approved'],
            ]);
        }

        if (! $result['is_approved']) {
            return back()->with('success', $result['moderation']['message']);
        }

        return back()->with('success', 'Comentário enviado com sucesso!');
    }

    public function storeThrottle(Request $request, ThrottleRequestsException $exception): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['content' => [$exception->getMessage()]],
            ], 429);
        }

        return back()->withErrors(['content' => $exception->getMessage()]);
    }

    public function storeDomainError(Request $request, \DomainException $exception): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => ['content' => [$exception->getMessage()]],
            ], 422);
        }

        return back()->withErrors(['content' => $exception->getMessage()]);
    }

    public function updateSuccess(array $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $result['is_approved']
                ? 'Comentário atualizado!'
                : 'Comentário atualizado, mas aguardando aprovação por conter links.',
            'comment' => $result['comment']->load('user'),
            'is_approved' => $result['is_approved'],
        ]);
    }

    public function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Não autorizado.'], 403);
    }

    public function updateValidationError(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], 422);
    }

    public function destroySuccess(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Comentário excluído com sucesso.',
        ]);
    }

    public function destroyValidationError(ValidationException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 403);
    }
}
