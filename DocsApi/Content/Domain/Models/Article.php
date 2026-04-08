<?php

namespace Src\Content\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_SCHEDULED,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'version' => 1,
        'featured' => false,
    ];

    protected $fillable = [
        'category_id',
        'title',
        'subtitle',
        'slug',
        'full_url',
        'excerpt',
        'content',
        'status',
        'featured_image',
        'image_source',
        'cover_media_id',
        'gallery_image_urls',
        'gallery_media',
        'video_urls',
        'author_name',
        'reading_time',
        'is_published',
        'published_at',
        'meta',
        'seo_title',
        'seo_description',
        'canonical_url',
        'featured',
        'version',
        'wp_post_id',
        'needs_review',
        'is_reviewed',
        'reviewed_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array',
        'cover_media_id' => 'integer',
        'gallery_image_urls' => 'array',
        'gallery_media' => 'array',
        'video_urls' => 'array',
        'featured' => 'boolean',
        'needs_review' => 'boolean',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($article) {
            if ($article->category_id) {
                $category = $article->relationLoaded('category') ? $article->category : Category::find($article->category_id);
                if ($category) {
                    $article->full_url = "/{$article->slug}";
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function incrementVersion(): self
    {
        $this->increment('version');
        $this->refresh();

        return $this;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getUrlAttribute(): string
    {
        return "/{$this->slug}";
    }

    public function getContentHtmlAttribute(): string
    {
        if (empty($this->content)) {
            return '';
        }

        // Se o conteúdo começar com <, provavelmente ainda é HTML (legado)
        if (str_starts_with(trim($this->content), '<')) {
            return $this->content;
        }

        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($this->content)->getContent();

        // Injetar classes para FAQ (Markdown -> HTML)
        // 1. Transforma o H3 em título clicável
        $html = preg_replace(
            '/<h3>FAQ:\s*(.*?)<\/h3>/i',
            '<h3 class="faq-accordion-title"><span>$1</span><i class="faq-chevron"></i></h3>',
            $html
        );

        // 2. Envolve o parágrafo seguinte em uma div faq-accordion-answer
        $html = preg_replace(
            '/(<h3 class="faq-accordion-title">.*?<\/h3>)\s*<p>(.*?)<\/p>/is',
            '$1<div class="faq-accordion-answer"><p>$2</p></div>',
            $html
        );

        // 3. Embedding de Vídeos do YouTube (Substitui links ou tags <a> inteiras)
        // Regex para capturar tags <a> que contêm links do YouTube ou links diretos
        $youtubeRegex = '/<a[^>]*href="(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})"[^>]*>.*?<\/a>|(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i';

        $html = preg_replace_callback($youtubeRegex, function ($matches) {
            $videoId = ! empty($matches[1]) ? $matches[1] : (! empty($matches[2]) ? $matches[2] : null);
            if (! $videoId) {
                return $matches[0];
            }

            return '<div class="article-video-wrapper"><iframe src="https://www.youtube.com/embed/'.$videoId.'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }, $html);

        return $html;
    }

    public function getFeaturedImageAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return \Illuminate\Support\Facades\Storage::disk('s3')->url($value);
    }
}
