<?php

namespace App\Http\Requests\Activity;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class ActivityIndexRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'q'         => 'nullable|string',
            'type'      => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'page'      => 'nullable|integer|min:1',
        ];
    }
}
