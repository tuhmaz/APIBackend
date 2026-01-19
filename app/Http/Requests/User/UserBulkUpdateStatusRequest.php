<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\Rule;

class UserBulkUpdateStatusRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'ids' => 'required|array',
            'ids.*' => ['integer', Rule::exists(\App\Models\User::class, 'id')],
            'status' => 'required|string|in:active,inactive,pending,banned',
        ];
    }
}
