<?php

namespace Src\Content\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\AdminArea\Domain\Models\Admin;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'content',
        'is_approved',
        'reviewed_at',
        'reviewer_id',
        'ai_corrected_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'reviewed_at' => 'datetime',
        'ai_corrected_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewer_id');
    }

    protected $appends = ['is_official', 'is_editable'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Scope a query to only include approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function getIsOfficialAttribute(): bool
    {
        // User model does not have is_admin — official comments are identified
        // by the reviewer_id being set (an admin reviewed/authored this comment)
        return $this->reviewer_id !== null;
    }

    public function getIsEditableAttribute(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // Editable if user is owner AND created less than 5 minutes ago
        return $this->user_id === auth()->id() && $this->created_at->gt(now()->subMinutes(5));
    }

    public function getContentAttribute($value)
    {
        // Add rel="nofollow" to all links
        // Using a regex for simplicity, but DOMDocument is safer for complex HTML
        if (empty($value)) {
            return $value;
        }

        // Simple regex to add rel="nofollow" if it's not already there
        // This is a basic implementation. For production, consider HTML Purifier or DOMDocument
        $pattern = '/<a\s+(?!.*\brel=)[^>]*href=["\'](?!javascript:)([^"\']*)["\'][^>]*>/i';

        return preg_replace_callback($pattern, function ($matches) {
            $tag = $matches[0];
            if (strpos($tag, 'rel=') === false) {
                return str_replace('<a ', '<a rel="nofollow" ', $tag);
            }

            return $tag;
        }, $value);
    }
}
