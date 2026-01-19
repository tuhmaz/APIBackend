<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class UserUpdateRolesPermissionsRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'roles'       => 'nullable|array',
            'permissions' => 'nullable|array',
        ];
    }
}

