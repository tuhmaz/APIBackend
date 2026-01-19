<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرح له بتنفيذ هذا الطلب.
     *
     * @return bool
     */
    public function authorize()
    {
        // يتم التحقق من الصلاحيات في الفئات الفرعية
        return true;
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     *
     * @return array
     */
    abstract public function rules();

    /**
     * الحصول على رسائل الخطأ المخصصة للقواعد المحددة.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'email' => 'حقل :attribute يجب أن يكون بريد إلكتروني صالح.',
            'unique' => 'قيمة :attribute مستخدمة بالفعل.',
            'min' => 'حقل :attribute يجب أن يكون على الأقل :min.',
            'max' => 'حقل :attribute يجب أن لا يتجاوز :max.',
            'string' => 'حقل :attribute يجب أن يكون نصًا.',
            'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صالحًا.',
            'array' => 'حقل :attribute يجب أن يكون مصفوفة.',
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'حقل :attribute يجب أن يكون ملفًا من نوع: :values.',
            'exists' => 'القيمة المحددة لـ :attribute غير صالحة.',
            'boolean' => 'حقل :attribute يجب أن يكون true أو false.',
            'confirmed' => 'تأكيد :attribute غير متطابق.',
            'regex' => 'تنسيق :attribute غير صالح.',
            'in' => 'القيمة المحددة لـ :attribute غير صالحة.',
        ];
    }

    /**
     * التعامل مع فشل التحقق من صحة الطلب.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // تسجيل أخطاء التحقق
        Log::warning('فشل التحقق من صحة الطلب', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
            'ip' => $this->ip(),
            'user_id' => $this->user() ? $this->user()->id : 'guest'
        ]);

        // إذا كان الطلب يتوقع JSON، أرجع استجابة JSON
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        // استخدام السلوك الافتراضي للتحقق من صحة الطلب
        parent::failedValidation($validator);
    }

    /**
     * تنظيف البيانات المدخلة قبل التحقق منها.
     *
     * @return array
     */
    protected function sanitizeInput()
    {
        $input = $this->all();
        
        // تنظيف الحقول النصية
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // إزالة علامات HTML الخطيرة
                $input[$key] = strip_tags($value);
                
                // تنظيف النص من محاولات حقن SQL
                $input[$key] = str_replace(['--', ';--', ';', '/*', '*/', 'UNION', 'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE'], '', $input[$key]);
            }
        }
        
        return $input;
    }

    /**
     * الحصول على البيانات المراد التحقق منها من الطلب.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->sanitizeInput();
    }
}
