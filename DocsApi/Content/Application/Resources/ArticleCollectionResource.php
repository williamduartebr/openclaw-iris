<?php

namespace Src\Content\Application\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'featured' => (bool) $this->featured,
            'cover_image_url' => $this->getRawOriginal('featured_image'),
            'image_source' => $this->image_source ?? 'ai',
            'cover_media_id' => $this->cover_media_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'funnel_stage' => $this->category->funnel_stage,
            ]),
            'author' => $this->author_name,
            'reading_time' => $this->reading_time,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'version' => $this->version,
            'url' => $this->url,
        ];
    }
}
