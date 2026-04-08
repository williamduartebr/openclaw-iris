<?php

namespace Src\Content\Domain\Services;

use Illuminate\Support\Str;
use Src\Content\Domain\Models\Article;

class ArticleSlugService
{
    public function generate(string $title): string
    {
        $slug = Str::slug($title);

        return mb_substr($slug, 0, 80);
    }

    public function ensureUnique(string $slug, ?int $excludeId = null): string
    {
        $original = $slug;
        $suffix = 2;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = mb_substr($original, 0, 76).'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $query = Article::withTrashed()->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
