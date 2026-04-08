<?php

namespace Src\Content\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'is_active',
        'verification_code',
        'email_verified_at',
        'category_slug',
        'extra_category_slugs',
        'lgpd_consent_at',
        'unsubscribe_token',
        'source_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'extra_category_slugs' => 'array',
        'lgpd_consent_at' => 'datetime',
    ];

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeByCategory(Builder $query, string $categorySlug): Builder
    {
        return $query->where('category_slug', $categorySlug);
    }
}
