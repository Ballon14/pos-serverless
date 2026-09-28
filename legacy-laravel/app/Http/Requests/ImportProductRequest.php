<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'], // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File harus diunggah.',
            'file.file' => 'File tidak valid.',
            'file.mimes' => 'File harus berformat .xlsx atau .csv.',
            'file.max' => 'Ukuran file tidak boleh lebih dari 10MB.',
        ];
    }
}
