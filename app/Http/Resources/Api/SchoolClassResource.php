<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'grade_name'  => $this->grade_name,
            'grade_level' => $this->grade_level,
            'country_id'  => $this->country_id,
            'subjects_count' => $this->subjects_count,
            'subjects'    => SubjectResource::collection($this->whenLoaded('subjects')),
            'semesters'   => SemesterResource::collection($this->whenLoaded('semesters')),
        ];
    }
}
