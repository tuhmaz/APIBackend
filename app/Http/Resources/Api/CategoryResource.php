<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * Get proper storage URL (ensures api subdomain is used)
     */
    private function getStorageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $url = Storage::url($path);

        // Fix: ensure api subdomain is used for storage URLs in production
        if (app()->environment('production') && str_contains($url, 'alemancenter.com') && !str_contains($url, 'api.alemancenter.com')) {
            $url = str_replace('alemancenter.com', 'api.alemancenter.com', $url);
        }

        return $url;
    }

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
            'icon_url'   => $this->getStorageUrl($this->icon),
            'image'      => $this->image,
            'image_url'  => $this->getStorageUrl($this->image),
            'icon_image' => $this->icon_image,
            'icon_image_url' => $this->getStorageUrl($this->icon_image),
            'news_count' => isset($this->news_count) ? (int) $this->news_count : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

