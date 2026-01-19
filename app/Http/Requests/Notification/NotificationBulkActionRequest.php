<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;

class NotificationBulkActionRequest extends BaseFormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'string',
            'action' => 'required|string|in:delete,mark-as-read',
        ];
    }
}
