<?php

namespace Src\Content\Application\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Src\Content\Application\Actions\StoreNewsletterAction;
use Src\Content\Application\Requests\StoreNewsletterRequest;
use Src\Content\Domain\Models\Category;
use Src\Content\Domain\Models\NewsletterSubscriber;
use Src\Shared\Domain\Services\AuthSEOService;

class NewsletterController extends Controller
{
    public function __construct(
        protected AuthSEOService $seoService
    ) {}

    public function create(Request $request): View
    {
        $this->seoService->setNewsletterVerificationMetaTags();

        $subscriber = $request->filled('email')
            ? NewsletterSubscriber::query()->where('email', $request->string('email'))->first()
            : null;

        $activeCategories = Cache::remember(
            'content_categories_active_ordered_v1',
            now()->addDays(7),
            fn () => Category::query()
                ->activeOrdered()
                ->get(['name', 'slug', 'description', 'role'])
        );

        $primaryCategory = $subscriber?->category_slug
            ? $activeCategories->firstWhere('slug', $subscriber->category_slug)
            : null;

        $selectedExtraCategorySlugs = old('extra_category_slugs', $subscriber?->extra_category_slugs ?? []);

        if (! is_array($selectedExtraCategorySlugs)) {
            $selectedExtraCategorySlugs = [];
        }

        $extraCategoryGroups = $activeCategories
            ->reject(fn (Category $category) => $primaryCategory && $category->slug === $primaryCategory->slug)
            ->groupBy(fn (Category $category) => $category->newsletterAudienceLabel());

        return view('shared::auth.verification-code', [
            'subscriber' => $subscriber,
            'primaryCategory' => $primaryCategory,
            'extraCategoryGroups' => $extraCategoryGroups,
            'selectedExtraCategorySlugs' => $selectedExtraCategorySlugs,
        ]);
    }

    public function store(StoreNewsletterRequest $request, StoreNewsletterAction $action)
    {
        $result = $action->execute(
            $request->email,
            $request->name,
            $request->category_slug,
            $request->source_url,
        );

        if ($result['status'] === 'already_subscribed') {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Este e-mail já está inscrito na newsletter.',
                ], 200);
            }

            return redirect()->back()->with('info', 'Este e-mail já está inscrito na newsletter.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Verifique seu e-mail para confirmar a inscrição!',
                'redirect_url' => route('newsletter.verify', ['email' => $request->email]),
            ], 201);
        }

        return redirect()->route('newsletter.verify', ['email' => $request->email]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:newsletter_subscribers,email',
            'code' => 'required|string|size:6',
            'extra_category_slugs' => 'nullable|array',
            'extra_category_slugs.*' => 'string|max:100|distinct',
        ]);

        $subscriber = NewsletterSubscriber::where('email', $request->email)->first();

        if ($subscriber->verification_code !== $request->code) {
            return back()
                ->withErrors(['code' => 'Código incorreto. Tente novamente.'])
                ->withInput();
        }

        $allowedSlugs = Cache::remember(
            'content_categories_active_slugs_v1',
            now()->addDays(7),
            fn () => Category::query()
                ->activeOrdered()
                ->pluck('slug')
        );

        $extraCategorySlugs = collect($request->input('extra_category_slugs', []))
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->unique()
            ->reject(fn ($slug) => $subscriber->category_slug && $slug === $subscriber->category_slug)
            ->intersect($allowedSlugs)
            ->values()
            ->all();

        $subscriber->update([
            'is_active' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'extra_category_slugs' => $extraCategorySlugs,
        ]);

        return redirect()
            ->route('content.index')
            ->with('success', 'Cadastro confirmado com sucesso! Sua inscrição na newsletter já está ativa.');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            abort(404);
        }

        $subscriber->update(['is_active' => false]);

        return view('content::newsletter.unsubscribed');
    }
}
