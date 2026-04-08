    <!-- Comments Section (Vue Island) -->
    <section class="max-w-[712px] mx-auto px-6 mb-20">
        <div id="comments-app" data-article-slug="{{ $article->slug }}"
            data-submit-url="{{ route('content.category.article.comment.store', ['categorySlug' => $article->category->slug, 'articleSlug' => $article->slug]) }}"
            data-comments="{{ json_encode($comments->items()) }}"
            data-user="{{ auth()->check() ? json_encode(auth()->user()) : '' }}"
            data-google-client-id="{{ config('services.google.client_id') }}"
            data-pagination-links="{{ $comments->links() }}">
        </div>
    </section>
