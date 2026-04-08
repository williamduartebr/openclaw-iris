<?php

namespace Src\Content\Application\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Src\Content\Application\Actions\DeleteCommentAction;
use Src\Content\Application\Actions\StoreCommentAction;
use Src\Content\Application\Actions\UpdateCommentAction;
use Src\Content\Application\Requests\StoreCommentRequest;
use Src\Content\Application\Requests\UpdateCommentRequest;
use Src\Content\Application\Services\ArticlePageQueryService;
use Src\Content\Application\Services\ArticleStructuredDataService;
use Src\Content\Application\Services\CommentResponseService;
use Src\Content\Domain\Services\ContentSEOService;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ContentSEOService $seoService,
        private readonly ArticlePageQueryService $articlePageQueryService,
        private readonly ArticleStructuredDataService $articleStructuredDataService,
        private readonly CommentResponseService $commentResponseService,
    ) {}

    public function index()
    {
        $categories = $this->articlePageQueryService->getActiveCategoriesWithPublishedCount();

        $this->seoService->setIndexMetaTags();

        return view('content::index', [
            'categories' => $categories,
        ]);
    }

    public function show(string $categorySlug, string $articleSlug)
    {
        $category = $this->articlePageQueryService->getCategoryOrFail($categorySlug);
        $article = $this->articlePageQueryService->getArticleForCategoryOrFail($category, $articleSlug);
        $comments = $this->articlePageQueryService->getCommentsPaginator($article);
        $relatedArticles = $this->articlePageQueryService->getRelatedArticlesPaginator($category, $article->id);

        $this->seoService->setArticleMetaTags($article);

        $schemas = $this->articleStructuredDataService->buildForArticle($article);

        return view('content::article', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'comments' => $comments,
            'articleSchemaJson' => $schemas['articleSchemaJson'],
            'breadcrumbSchemaJson' => $schemas['breadcrumbSchemaJson'],
        ]);
    }

    public function showShort(string $articleSlug)
    {
        $article = $this->articlePageQueryService->findPublishedArticleWithActiveCategory($articleSlug);

        if (! $article) {
            abort(404);
        }

        return $this->show($article->category->slug, $articleSlug);
    }

    public function categoryIndex(string $categorySlug)
    {
        $category = $this->articlePageQueryService->getCategoryOrFail($categorySlug);
        $articles = $this->articlePageQueryService->getCategoryArticlesPaginator($category);

        $this->seoService->setCategoryMetaTags($category);

        return view('content::category', [
            'category' => $category,
            'articles' => $articles,
        ]);
    }

    public function storeComment(StoreCommentRequest $request, string $categorySlug, string $articleSlug, StoreCommentAction $action)
    {
        try {
            $articleSlug = $this->commentResponseService->normalizeArticleSlug($categorySlug, $articleSlug);

            $result = $action->execute(
                $articleSlug,
                $request->validated(),
                auth()->id()
            );

            return $this->commentResponseService->storeSuccess($request, $result);

        } catch (ThrottleRequestsException $e) {
            return $this->commentResponseService->storeThrottle($request, $e);

        } catch (\DomainException $e) {
            return $this->commentResponseService->storeDomainError($request, $e);
        }
    }

    public function updateComment(UpdateCommentRequest $request, string $categorySlug, string $articleSlug, int $commentId, UpdateCommentAction $action)
    {
        try {
            $this->commentResponseService->normalizeArticleSlug($categorySlug, $articleSlug);

            $result = $action->execute(
                $commentId,
                $request->validated(),
                auth()->id()
            );

            return $this->commentResponseService->updateSuccess($result);

        } catch (AuthorizationException $e) {
            return $this->commentResponseService->unauthorized();

        } catch (ValidationException $e) {
            return $this->commentResponseService->updateValidationError($e);
        }
    }

    public function destroyComment(Request $request, string $categorySlug, string $articleSlug, int $commentId, DeleteCommentAction $action)
    {
        try {
            $this->commentResponseService->normalizeArticleSlug($categorySlug, $articleSlug);

            $action->execute($commentId, auth()->id());

            return $this->commentResponseService->destroySuccess();

        } catch (AuthorizationException $e) {
            return $this->commentResponseService->unauthorized();

        } catch (ValidationException $e) {
            return $this->commentResponseService->destroyValidationError($e);
        }
    }
}
