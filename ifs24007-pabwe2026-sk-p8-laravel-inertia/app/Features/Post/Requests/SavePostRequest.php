<?php

namespace App\Features\Post\Requests;

use App\Features\Post\Enums\PostCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dijaga middleware 'auth' dan PostPolicy
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.enum' => 'Kategori tidak valid.',
            'excerpt.max' => 'Ringkasan maksimal 500 karakter.',
            'content.required' => 'Isi artikel wajib diisi.',
        ];
    }
}
