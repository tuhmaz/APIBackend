<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'subject_name' => $this->subject_name,
            'grade_level'=> $this->grade_level,
            'articles_count' => $this->articles_count,
            'files_count' => $this->files_count,
        ];
    }
}
