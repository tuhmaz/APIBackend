<?php

namespace App\Http\Requests\Semester;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class SemesterStoreRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
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
