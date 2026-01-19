<?php

namespace App\Http\Requests;

class SecureDocumentUploadRequest extends BaseFormRequest
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
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
                'max:10240', // 10 ميجابايت كحد أقصى
                function ($attribute, $value, $fail) {
                    // التحقق من محتوى الملف
                    try {
                        // التحقق من وجود محتوى مشبوه في الملف
                        $content = file_get_contents($value->getRealPath());
                        $suspiciousPatterns = [
                            '<?php',
                            '<?=',
                            '<script>',
                            'javascript:',
                            'vbscript:',
                            'data:text/html',
                            'ActiveXObject',
                            'eval(',
                            'document.cookie',
                            'document.write(',
                            'window.location'
                        ];
                        
                        foreach ($suspiciousPatterns as $pattern) {
                            if (stripos($content, $pattern) !== false) {
                                $fail('تم اكتشاف محتوى مشبوه في المستند.');
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        $fail('فشل في التحقق من محتوى المستند. الرجاء المحاولة مرة أخرى.');
                    }
                },
            ],
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
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
            'file.mimes' => 'يجب أن يكون المستند من نوع: pdf، doc، docx، xls، xlsx، ppt، pptx، أو txt.',
            'file.max' => 'حجم المستند يجب أن لا يتجاوز 10 ميجابايت.',
            'description.max' => 'الوصف يجب أن لا يتجاوز 500 حرف.',
            'category.max' => 'الفئة يجب أن لا تتجاوز 100 حرف.',
        ]);
    }
}
