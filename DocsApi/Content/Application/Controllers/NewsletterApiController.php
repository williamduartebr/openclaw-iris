<?php

namespace Src\Content\Application\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\NewsletterSubscriber;

class NewsletterApiController extends Controller
{
    public function subscribers(Request $request): JsonResponse
    {
        $query = NewsletterSubscriber::query()
            ->where('is_active', true)
            ->whereNotNull('email_verified_at');

        if ($request->filled('category_slug')) {
            $query->where('category_slug', $request->category_slug);
        }

        if ($request->filled('since')) {
            $since = match ($request->since) {
                '5minutes' => now()->subMinutes(5),
                '1hour' => now()->subHour(),
                '1day' => now()->subDay(),
                default => now()->subMinutes((int) $request->since),
            };
            $query->where('email_verified_at', '>=', $since);
        }

        $subscribers = $query->paginate($request->integer('per_page', 50));

        return response()->json($subscribers);
    }

    public function stats(): JsonResponse
    {
        $stats = Cache::remember(
            'content_newsletter_stats_v1',
            now()->addHours(6),
            function () {
                $byCategory = NewsletterSubscriber::query()
                    ->where('is_active', true)
                    ->whereNotNull('email_verified_at')
                    ->selectRaw('category_slug, COUNT(*) as total')
                    ->groupBy('category_slug')
                    ->pluck('total', 'category_slug');

                $totalActive = NewsletterSubscriber::where('is_active', true)
                    ->whereNotNull('email_verified_at')
                    ->count();

                $totalUnverified = NewsletterSubscriber::whereNull('email_verified_at')->count();

                return [
                    'total_active' => $totalActive,
                    'total_unverified' => $totalUnverified,
                    'by_category' => $byCategory,
                ];
            }
        );

        return response()->json($stats);
    }

    public function recentArticles(Request $request): JsonResponse
    {
        $days = $request->integer('days', 7);
        $categorySlug = $request->input('category_slug');
        $limit = $request->integer('limit', 20);

        $cacheKey = $categorySlug
            ? "content_recent_articles_v1:days:{$days}:category:{$categorySlug}"
            : "content_recent_articles_v1:days:{$days}";

        $articles = Cache::remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($days, $categorySlug, $limit) {
                $query = Article::query()
                    ->where('is_published', true)
                    ->where('published_at', '>=', now()->subDays($days))
                    ->with('category')
                    ->orderByDesc('published_at');

                if ($categorySlug) {
                    $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
                }

                return $query->limit($limit)->get();
            }
        );

        return response()->json([
            'data' => $articles->map(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'url' => url($article->full_url),
                'published_at' => $article->published_at->toIso8601String(),
                'category' => $article->category?->name,
                'category_slug' => $article->category?->slug,
                'featured_image' => $article->featured_image,
            ]),
        ]);
    }
}
