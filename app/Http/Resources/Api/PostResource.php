<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\FileResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'content'          => $this->content,
            'image'            => $this->image,
            'image_url'        => $this->image_url, // Assuming Accessor exists
            'meta_description' => $this->meta_description,
            'keywords'         => $this->keywords,
            'views'            => (int) $this->views,
            'views_count'      => (int) $this->views,
            'created_at'       => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'is_active'        => (bool) $this->is_active,
            'is_featured'      => (bool) $this->is_featured,
            'attachments'      => FileResource::collection($this->whenLoaded('attachments')),
            'author_id'        => $this->author_id,
            'author'           => $this->whenLoaded('author', function() {
                return $this->author ? [
                    'id'   => $this->author->id,
                    'name' => $this->author->name,
                ] : null;
            }),
            'category'         => $this->whenLoaded('category', function() {
                return [
                    'id'   => $this->category->id,
                    'name' => $this->category->name
                ];
            }),
        ];
    }
}
