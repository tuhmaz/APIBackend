<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseApiResource extends JsonResource
{
    protected function success($data, $extra = [])
    {
        return array_merge([
            'success' => true,
            'data'    => $data,
        ], $extra);
    }
}
