<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ArticleRequest extends BaseFormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرح له بتنفيذ هذا الطلب.
     *
     * @return bool
     */
    public function authorize()
    {
        // التحقق من أن المستخدم لديه صلاحية إدارة المقالات
        return auth()->check() && auth()->user()->can('manage articles');
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('articles')->ignore($this->route('article')),
            ],
            'content' => 'required|string|min:100',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
        ];

        // إذا كانت الحالة "scheduled"، فإن تاريخ النشر مطلوب
        if ($this->input('status') === 'scheduled') {
            $rules['published_at'] = 'required|date|after:now';
        }

        return $rules;
    }

    /**
     * الحصول على رسائل الخطأ المخصصة للقواعد المحددة.
     *
     * @return array
     */
    public function messages()
    {
        return array_merge(parent::messages(), [
            'title.required' => 'عنوان المقال مطلوب.',
            'slug.required' => 'الرابط المختصر مطلوب.',
            'slug.regex' => 'الرابط المختصر يجب أن يحتوي فقط على أحرف صغيرة، أرقام وشرطات.',
            'slug.unique' => 'الرابط المختصر مستخدم بالفعل.',
            'content.required' => 'محتوى المقال مطلوب.',
            'content.min' => 'محتوى المقال يجب أن يكون على الأقل 100 حرف.',
            'category_id.required' => 'التصنيف مطلوب.',
            'category_id.exists' => 'التصنيف المحدد غير موجود.',
            'tags.*.exists' => 'أحد الوسوم المحددة غير موجود.',
            'status.required' => 'حالة المقال مطلوبة.',
            'status.in' => 'حالة المقال يجب أن تكون مسودة، منشور أو مجدول.',
            'published_at.required' => 'تاريخ النشر مطلوب للمقالات المجدولة.',
            'published_at.after' => 'تاريخ النشر يجب أن يكون في المستقبل.',
            'featured_image.image' => 'الصورة البارزة يجب أن تكون صورة.',
            'featured_image.mimes' => 'الصورة البارزة يجب أن تكون من نوع: jpg، jpeg، png، أو webp.',
            'featured_image.max' => 'حجم الصورة البارزة يجب أن لا يتجاوز 5 ميجابايت.',
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
        
        // معالجة المحتوى للسماح بعلامات HTML آمنة
        if (isset($input['content'])) {
            // السماح بعلامات HTML آمنة محددة في المحتوى
            $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><s><blockquote><pre><code>';
            $allowedTags .= '<ul><ol><li><table><thead><tbody><tr><th><td><a><img><figure><figcaption><hr>';
            
            $input['content'] = strip_tags($input['content'], $allowedTags);
            
            // تنظيف الروابط من السكريبتات الضارة
            $input['content'] = preg_replace('/<a([^>]*)javascript:([^>]*)>/i', '<a>', $input['content']);
            
            // تنظيف الصور من السكريبتات الضارة
            $input['content'] = preg_replace('/<img([^>]*)onerror=([^>]*)>/i', '<img>', $input['content']);
        }
        
        // تنظيف الملخص
        if (isset($input['excerpt'])) {
            $input['excerpt'] = strip_tags($input['excerpt']);
        }
        
        // تنظيف العنوان والوصف لمحركات البحث
        if (isset($input['meta_title'])) {
            $input['meta_title'] = strip_tags($input['meta_title']);
        }
        
        if (isset($input['meta_description'])) {
            $input['meta_description'] = strip_tags($input['meta_description']);
        }
        
        if (isset($input['meta_keywords'])) {
            $input['meta_keywords'] = strip_tags($input['meta_keywords']);
        }
        
        return $input;
    }
}
