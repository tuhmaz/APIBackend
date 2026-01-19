<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'bio'      => $this->bio,
            'google_id'=> $this->google_id,
            'profile_photo_path' => $this->profile_photo_path,
            'profile_photo_url'  => $this->profile_photo_url,
            'email_verified_at'  => $this->email_verified_at,
            'last_activity' => $this->last_activity,
            'last_seen'     => $this->last_seen,
            'status'        => $this->status,
            'is_online'     => $this->isOnline(),
            'job_title' => $this->job_title,
            'gender'    => $this->gender,
            'country'   => $this->country,
            'social_links' => $this->social_links,
            'roles'    => $this->whenLoaded('roles', function() {
                return $this->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name
                    ];
                });
            }, []),
            'permissions' => $this->getAllPermissions()->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
