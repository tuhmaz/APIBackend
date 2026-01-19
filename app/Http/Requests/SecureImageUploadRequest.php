<?php

namespace App\Http\Requests;

class SecureImageUploadRequest extends BaseFormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرح له بتنفيذ هذا الطلب.
     *
     * @return bool
     */
    public function authorize()
    {
        // التحقق من أن المستخدم مسجل الدخول
        return auth()->check();
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:10240', // 10 ميجابايت كحد أقصى
                function ($attribute, $value, $fail) {
                    // التحقق من أبعاد الصورة
                    try {
                        $image = \Intervention\Image\Facades\Image::make($value->getRealPath());
                        
                        // التحقق من الحد الأقصى للأبعاد (8000×8000 بكسل)
                        if ($image->width() > 8000 || $image->height() > 8000) {
                            $fail('أبعاد الصورة كبيرة جدًا. يجب أن لا تتجاوز 8000×8000 بكسل.');
                        }
                        
                        // التحقق من الحد الأدنى للأبعاد (10×10 بكسل)
                        if ($image->width() < 10 || $image->height() < 10) {
                            $fail('أبعاد الصورة صغيرة جدًا. يجب أن لا تقل عن 10×10 بكسل.');
                        }
                    } catch (\Exception $e) {
                        $fail('فشل في التحقق من أبعاد الصورة. تأكد من أن الملف صورة صالحة.');
                    }
                },
            ],
            'width' => 'nullable|integer|min:10|max:8000',
            'quality' => 'nullable|integer|min:10|max:100',
            'convert_to_webp' => 'nullable|boolean',
            'create_responsive' => 'nullable|boolean',
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
            'file.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'file.mimes' => 'يجب أن تكون الصورة من نوع: jpg، jpeg، png، gif، أو webp.',
            'file.max' => 'حجم الصورة يجب أن لا يتجاوز 10 ميجابايت.',
            'width.integer' => 'العرض يجب أن يكون رقمًا صحيحًا.',
            'width.min' => 'العرض يجب أن لا يقل عن :min بكسل.',
            'width.max' => 'العرض يجب أن لا يتجاوز :max بكسل.',
            'quality.integer' => 'الجودة يجب أن تكون رقمًا صحيحًا.',
            'quality.min' => 'الجودة يجب أن لا تقل عن :min%.',
            'quality.max' => 'الجودة يجب أن لا تتجاوز :max%.',
        ]);
    }
}
