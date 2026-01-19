<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class TrustedIpResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ip_address' => $this->ip_address,
            'reason' => $this->reason,
            'added_at' => $this->added_at,
            'added_by' => $this->added_by,
            'added_by_user' => new UserResource($this->whenLoaded('addedBy')),
        ];
    }
}

