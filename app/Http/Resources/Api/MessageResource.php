<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id'             => $this->id,
            'conversation_id'=> $this->conversation_id,
            'sender_id'      => $this->sender_id,
            'subject'        => $this->subject,
            'body'           => $this->body,
            'is_chat'        => (bool) $this->is_chat,
            'is_draft'       => (bool) $this->is_draft,
            'is_important'   => (bool) $this->is_important,
            'read'           => (bool) $this->read,
            'sender'         => new UserResource($this->whenLoaded('sender')),
            'created_at'     => $this->created_at,
        ];

        if ($this->relationLoaded('conversation') && $this->conversation) {
            $conv = $this->conversation;
            $recipient = ($conv->user1_id == $this->sender_id) ? $conv->user2 : $conv->user1;
            if ($recipient) {
                $data['recipient'] = new UserResource($recipient);
            }
        }

        return $data;
    }
}

