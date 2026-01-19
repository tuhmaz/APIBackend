<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\Rule;

class UserStoreRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique(\App\Models\User::class)],
            'password'  => 'required|string|min:8|confirmed',
            'role'      => 'required|string',
            'phone'     => 'nullable|string|max:15',
            'bio'       => 'nullable|string',
            'job_title' => 'nullable|string|max:255',
            'gender'    => 'nullable|string|max:50',
            'country'   => 'nullable|string|max:100',
            'status'    => 'nullable|string|in:active,inactive,pending,banned',
            'social_links' => 'nullable|array',
            'profile_photo' => 'nullable|image|max:2048',
        ];
    }
}

