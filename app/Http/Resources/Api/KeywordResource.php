<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class KeywordResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'keyword' => $this->keyword,
        ];
    }
}
