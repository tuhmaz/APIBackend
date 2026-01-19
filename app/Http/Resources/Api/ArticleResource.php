<?php

namespace App\Http\Resources\Api;

class ArticleResource extends BaseApiResource
{
    public function toArray($request)
    {
        return $this->success([
            'id'             => $this->id,
            'title'          => $this->title,
            'content'        => $this->content,
            'meta_description'=> $this->meta_description,
            'status'         => $this->status,
            'visit_count'    => $this->visit_count,
            'image'          => $this->image,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,

            // علاقات
            'class'     => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'subject'   => new SubjectResource($this->whenLoaded('subject')),
            'semester'  => new SemesterResource($this->whenLoaded('semester')),
            'files'     => FileResource::collection($this->whenLoaded('files')),
            'keywords'  => KeywordResource::collection($this->whenLoaded('keywords')),
        ]);
    }
}
