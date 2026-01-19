<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'is_active'  => (bool) $this->is_active,
            'country'    => $this->country ?? null,
            'parent_id'  => $this->parent_id,
            'parent'     => $this->whenLoaded('parent', function() {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                ];
            }),
            'depth'      => $this->depth,
            'icon'       => $this->icon,
            'icon_url'   => $this->icon ? Storage::url($this->icon) : null,
            'image'      => $this->image,
            'image_url'  => $this->image ? Storage::url($this->image) : null,
            'icon_image' => $this->icon_image,
            'icon_image_url' => $this->icon_image ? Storage::url($this->icon_image) : null,
            'news_count' => isset($this->news_count) ? (int) $this->news_count : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

