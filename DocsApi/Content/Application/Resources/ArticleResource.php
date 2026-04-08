<?php

namespace Src\Content\Application\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body_md' => $this->getRawOriginal('content'),
            'status' => $this->status,
            'featured' => (bool) $this->featured,
            'cover_image_url' => $this->getRawOriginal('featured_image'),
            'image_source' => $this->image_source ?? 'ai',
            'cover_media_id' => $this->cover_media_id,
            'gallery_image_urls' => $this->gallery_image_urls ?? [],
            'gallery_media' => $this->gallery_media,
            'video_urls' => $this->video_urls ?? [],
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'funnel_stage' => $this->category->funnel_stage,
            ]),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ])),
            'author' => $this->author_name,
            'reading_time' => $this->reading_time,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'canonical_url' => $this->canonical_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'version' => $this->version,
            'url' => $this->url,
        ];
    }
}
