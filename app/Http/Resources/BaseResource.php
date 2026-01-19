<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    public function with($request)
    {
        return [
            'success' => true
        ];
    }

    public function withResponse($request, $response)
    {
        $response->header('Content-Type', 'application/json; charset=utf-8');
    }
}
