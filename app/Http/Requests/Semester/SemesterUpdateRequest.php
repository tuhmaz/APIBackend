<?php

namespace App\Http\Requests\Semester;

use App\Http\Requests\BaseFormRequest;

class SemesterUpdateRequest extends BaseFormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'semester_name' => 'required|string|max:255',
            'grade_level'   => 'required|integer',
            'country'       => 'required|string',
        ];
    }
}

