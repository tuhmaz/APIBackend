<?php

namespace App\Http\Requests\SecureFile;

use App\Http\Requests\BaseFormRequest;

class SecureFileViewRequest extends BaseFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'path'  => 'required|string',
            'token' => 'required|string',
        ];
    }
}

