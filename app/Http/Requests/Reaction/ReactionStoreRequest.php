<?php

namespace App\Http\Requests\Reaction;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class ReactionStoreRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'comment_id' => 'required|exists:comments,id',
            'type'       => 'required|string|max:50',
        ];
    }
}
