<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\Rule;

class UserBulkDeleteRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'user_ids' => 'required|array',
            'user_ids.*' => ['integer', Rule::exists(\App\Models\User::class, 'id')],
        ];
    }
}

