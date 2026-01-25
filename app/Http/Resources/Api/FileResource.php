<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'file_name'   => $this->file_name,
            'file_path'   => $this->file_path,
            'file_url'    => Storage::url($this->file_path),
            'file_type'   => $this->file_type,
            'file_size'   => $this->file_size,
            'mime_type'   => $this->mime_type,
            'category'    => $this->file_category,
            'file_category' => $this->file_category,
            'download_count' => $this->download_count ?? 0,
            'views_count' => $this->views_count ?? 0,
        ];
    }
}
