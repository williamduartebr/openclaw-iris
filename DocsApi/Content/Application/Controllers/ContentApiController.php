<?php

namespace Src\Content\Application\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Src\Content\Application\Requests\CreateArticleRequest;
use Src\Content\Application\Requests\ListArticlesRequest;
use Src\Content\Application\Requests\PatchArticleRequest;
use Src\Content\Application\Requests\UpdateArticleRequest;
use Src\Content\Application\Resources\ArticleCollectionResource;
use Src\Content\Application\Resources\ArticleResource;
use Src\Content\Application\Services\ArticleCrudService;
use Src\Content\Application\Services\ContentApiArticleQueryService;
use Src\Content\Application\Services\ContentApiResponseService;
use Src\Content\Domain\Exceptions\InvalidMediaReferenceException;
use Src\Content\Domain\Exceptions\InvalidStatusTransitionException;
use Src\Content\Domain\Exceptions\VersionConflictException;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Services\ArticleLifecycleService;

class ContentApiController extends Controller
{
    public function __construct(
        private readonly ArticleCrudService $crudService,
        private readonly ArticleLifecycleService $lifecycleService,
        private readonly ContentApiArticleQueryService $articleQueryService,
        private readonly ContentApiResponseService $responseService,
    ) {}

    // ── Health ──────────────────────────────────────────────────────

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'module' => 'content',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ── Categories ─────────────────────────────────────────────────

    public function categories(): JsonResponse
    {
        $categories = $this->articleQueryService->getActiveCategories();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'role' => $cat->role,
                'funnel_stage' => $cat->funnel_stage,
            ]),
            'total' => $categories->count(),
        ]);
    }

    // ── Articles: busca exata por slug ─────────────────────────────

    public function showBySlug(string $slug): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findBySlug($slug);

        if (! $article) {
            return $this->responseService->articleNotFound($slug);
        }

        return new ArticleResource($article);
    }

    // ── Articles: listagem ─────────────────────────────────────────

    public function index(ListArticlesRequest $request): JsonResponse
    {
        $paginated = $this->articleQueryService->list($request);

        return ArticleCollectionResource::collection($paginated)->response();
    }

    // ── Articles: create ───────────────────────────────────────────

    public function store(CreateArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->crudService->create($request->validated());

            return (new ArticleResource($article))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidMediaReferenceException $e) {
            return $this->responseService->invalidMediaReference($e);
        }
    }

    // ── Articles: show by id ───────────────────────────────────────

    public function show(int $id): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findById($id);

        if (! $article) {
            return $this->responseService->articleNotFound();
        }

        return new ArticleResource($article);
    }

    // ── Articles: update (full) ────────────────────────────────────

    public function update(UpdateArticleRequest $request, int $id): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findById($id);

        if (! $article) {
            return $this->responseService->articleNotFound();
        }

        try {
            $article = $this->crudService->update($article, $request->validated());

            return new ArticleResource($article);
        } catch (VersionConflictException $e) {
            return $this->responseService->versionConflict($e);
        } catch (InvalidStatusTransitionException $e) {
            return $this->responseService->invalidStatusTransition($e);
        } catch (InvalidMediaReferenceException $e) {
            return $this->responseService->invalidMediaReference($e);
        }
    }

    // ── Articles: patch (partial) ──────────────────────────────────

    public function patch(PatchArticleRequest $request, int $id): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findById($id);

        if (! $article) {
            return $this->responseService->articleNotFound();
        }

        try {
            $article = $this->crudService->patch($article, $request->validated());

            return new ArticleResource($article);
        } catch (VersionConflictException $e) {
            return $this->responseService->versionConflict($e);
        } catch (InvalidStatusTransitionException $e) {
            return $this->responseService->invalidStatusTransition($e);
        } catch (InvalidMediaReferenceException $e) {
            return $this->responseService->invalidMediaReference($e);
        }
    }

    // ── Articles: delete (soft) ────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $article = $this->articleQueryService->findById($id);

        if (! $article) {
            return $this->responseService->articleNotFound();
        }

        $version = request()->input('version');

        try {
            $this->crudService->delete($article, $version ? (int) $version : null);

            return $this->responseService->articleSoftDeleted($article);
        } catch (VersionConflictException $e) {
            return $this->responseService->versionConflict($e);
        }
    }

    // ── Lifecycle: restore ─────────────────────────────────────────

    public function restore(int $id): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findTrashedById($id);

        if (! $article) {
            return $this->responseService->articleNotFoundOrNotDeleted();
        }

        $article = $this->crudService->restore($id);

        return new ArticleResource($article);
    }

    // ── Lifecycle: publish ─────────────────────────────────────────

    public function publish(int $id): JsonResponse|ArticleResource
    {
        return $this->runLifecycleTransition(
            $id,
            fn (Article $article): Article => $this->lifecycleService->publish($article)
        );
    }

    // ── Lifecycle: unpublish ───────────────────────────────────────

    public function unpublish(int $id): JsonResponse|ArticleResource
    {
        return $this->runLifecycleTransition(
            $id,
            fn (Article $article): Article => $this->lifecycleService->unpublish($article)
        );
    }

    // ── Lifecycle: schedule ────────────────────────────────────────

    public function schedule(int $id): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findById($id);

        if (! $article) {
            return $this->responseService->articleNotFound();
        }

        $publishedAt = request()->input('published_at');

        if (! $publishedAt) {
            return $this->responseService->schedulePublishedAtRequired();
        }

        $date = Carbon::parse($publishedAt);

        if ($date->isPast()) {
            return $this->responseService->schedulePublishedAtFuture();
        }

        try {
            $article = $this->lifecycleService->schedule($article, $date);
            $article = $this->articleQueryService->loadRelations($article);

            return new ArticleResource($article);
        } catch (InvalidStatusTransitionException $e) {
            return $this->responseService->invalidStatusTransition($e);
        }
    }

    // ── Lifecycle: archive ─────────────────────────────────────────

    public function archive(int $id): JsonResponse|ArticleResource
    {
        return $this->runLifecycleTransition(
            $id,
            fn (Article $article): Article => $this->lifecycleService->archive($article)
        );
    }

    private function runLifecycleTransition(int $id, callable $transition): JsonResponse|ArticleResource
    {
        $article = $this->articleQueryService->findById($id);

        if (! $article) {
            return $this->responseService->articleNotFound();
        }

        try {
            $article = $transition($article);
            $article = $this->articleQueryService->loadRelations($article);

            return new ArticleResource($article);
        } catch (InvalidStatusTransitionException $e) {
            return $this->responseService->invalidStatusTransition($e);
        }
    }
}
