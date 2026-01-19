<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SecurityLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'event_type'   => $this->event_type,
            'severity'     => $this->severity,
            'ip_address'   => $this->ip_address,
            'route'        => $this->route,
            'user'         => new UserResource($this->whenLoaded('user')),
            'is_resolved'  => (bool) $this->is_resolved,
            'resolved_at'  => $this->resolved_at,
            'resolved_by'  => $this->resolved_by,
            'resolution_notes' => $this->resolution_notes,
            'created_at'   => $this->created_at,
        ];
    }
}

