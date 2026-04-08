<?php

namespace Src\Content\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const FUNNEL_TOFU = 'TOFU';

    public const FUNNEL_MOFU = 'MOFU';

    public const FUNNEL_BOFU = 'BOFU';

    public const VALID_FUNNEL_STAGES = [
        self::FUNNEL_TOFU,
        self::FUNNEL_MOFU,
        self::FUNNEL_BOFU,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'role',
        'role_description',
        'funnel_stage',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActiveOrdered(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function publishedArticles(): HasMany
    {
        return $this->articles()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function newsletterAudienceLabel(): string
    {
        return match ($this->role) {
            'B2B_RECRUITING', 'B2B_RETENTION' => 'Para negócios automotivos',
            'B2C_UTILITY' => 'Novidades e utilidade',
            'B2C_CONVERSION' => 'Serviços e soluções',
            default => 'Conteúdo geral',
        };
    }
}
