<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LegacyImportRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('import legacy') ?? true; }

    public function rules(): array
    {
        return [
            'host' => ['required','string'],
            'port' => ['nullable','integer'],
            'database' => ['required','string'],
            'username' => ['required','string'],
            'password' => ['nullable','string'],
            'charset' => ['nullable','string'],
            'collation' => ['nullable','string'],
            'dry_run' => ['nullable','boolean'],
            'truncate_before' => ['nullable','boolean'],
            'preserve_ids' => ['nullable','boolean'],
            'tables' => ['nullable','array'],
            'tables.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'port' => $this->input('port', 3306),
            'charset' => $this->input('charset', 'utf8mb4'),
            'collation' => $this->input('collation', 'utf8mb4_unicode_ci'),
            'dry_run' => (bool)$this->input('dry_run', false),
            'truncate_before' => (bool)$this->input('truncate_before', false),
            'preserve_ids' => (bool)$this->input('preserve_ids', true),
        ]);
    }
}
