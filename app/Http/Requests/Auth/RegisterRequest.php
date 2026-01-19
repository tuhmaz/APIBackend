<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

use Illuminate\Validation\Rule;

class RegisterRequest extends BaseFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', 'max:255', Rule::unique(\App\Models\User::class)],
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}

