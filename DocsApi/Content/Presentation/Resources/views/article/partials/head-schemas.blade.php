<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
    {!! $articleSchemaJson !!}
</script>

<!-- Breadcrumb Schema -->
<script type="application/ld+json">
    {!! $breadcrumbSchemaJson !!}
</script>

@php
$commentsSchema = $comments->map(function ($comment) {
    return [
        '@type' => 'Comment',
        'text' => strip_tags($comment->content),
        'author' => [
            '@type' => 'Person',
            'name' => $comment->user->name,
        ],
        'dateCreated' => $comment->created_at->toIso8601String(),
    ];
})->values()->toArray();

$commentsSchemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'datePublished' => $article->published_at->toIso8601String(),
    'commentCount' => $article->comments()->approved()->count(),
    'comment' => $commentsSchema,
];
@endphp
<script type="application/ld+json">
    {!! json_encode($commentsSchemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
