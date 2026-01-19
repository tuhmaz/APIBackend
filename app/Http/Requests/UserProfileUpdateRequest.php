<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserProfileUpdateRequest extends BaseFormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرح له بتنفيذ هذا الطلب.
     *
     * @return bool
     */
    public function authorize()
    {
        // التحقق من أن المستخدم يقوم بتحديث ملفه الشخصي فقط
        return Auth::check() && Auth::id() === (int) $this->route('id');
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     *
     * @return array
     */
    public function rules()
    {
        $userId = Auth::id();

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(\App\Models\User::class)->ignore($userId),
            ],
            'phone' => 'nullable|string|max:20|regex:/^[0-9\+\-\(\) ]+$/',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:8|confirmed',
            'current_password' => 'required_with:password|string',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|string|url|max:255',
            'social_links.twitter' => 'nullable|string|url|max:255',
            'social_links.instagram' => 'nullable|string|url|max:255',
            'social_links.linkedin' => 'nullable|string|url|max:255',
            'social_links.youtube' => 'nullable|string|url|max:255',
            'preferences' => 'nullable|array',
            'preferences.notifications' => 'nullable|boolean',
            'preferences.newsletter' => 'nullable|boolean',
            'preferences.language' => 'nullable|string|in:ar,en',
            'preferences.theme' => 'nullable|string|in:light,dark,auto',
        ];
    }

    /**
     * الحصول على رسائل الخطأ المخصصة للقواعد المحددة.
     *
     * @return array
     */
    public function messages()
    {
        return array_merge(parent::messages(), [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.regex' => 'رقم الهاتف يحتوي على أحرف غير مسموح بها.',
            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'current_password.required_with' => 'كلمة المرور الحالية مطلوبة عند تغيير كلمة المرور.',
            'avatar.image' => 'الصورة الشخصية يجب أن تكون صورة.',
            'avatar.mimes' => 'الصورة الشخصية يجب أن تكون من نوع: jpg، jpeg، png، gif، أو webp.',
            'avatar.max' => 'حجم الصورة الشخصية يجب أن لا يتجاوز 2 ميجابايت.',
            'social_links.*.url' => 'رابط :attribute يجب أن يكون عنوان URL صالح.',
            'preferences.language.in' => 'اللغة المختارة غير مدعومة.',
            'preferences.theme.in' => 'السمة المختارة غير مدعومة.',
        ]);
    }

    /**
     * تنظيف البيانات المدخلة بشكل خاص.
     *
     * @return array
     */
    protected function sanitizeInput()
    {
        $input = parent::sanitizeInput();
        
        // تنظيف إضافي للحقول النصية
        if (isset($input['bio'])) {
            // السماح بعلامات HTML آمنة محددة فقط في السيرة الذاتية
            $input['bio'] = strip_tags($input['bio'], '<p><br><strong><em><ul><ol><li><a>');
        }
        
        // تنظيف الروابط الاجتماعية
        if (isset($input['social_links']) && is_array($input['social_links'])) {
            foreach ($input['social_links'] as $platform => $url) {
                if (!empty($url)) {
                    // التأكد من أن الرابط آمن
                    $input['social_links'][$platform] = filter_var($url, FILTER_SANITIZE_URL);
                }
            }
        }
        
        return $input;
    }
}
