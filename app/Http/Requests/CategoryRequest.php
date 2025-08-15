<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->method()) {
            'POST'   => $this->user()->can('category_create'),
            'PATCH'  => $this->user()->can('category_edit'),
            'DELETE' => $this->user()->can('category_delete'),
            default  => true,
        };
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                $categoryId ? "unique:categories,slug,{$categoryId}" : 'unique:categories,slug'
            ],
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                // Empêcher qu'une catégorie soit son propre parent
                $categoryId ? "not_in:{$categoryId}" : '',
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Ce slug est déjà utilisé par une autre catégorie.',
            'slug.regex' => 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets.',
            'parent_id.not_in' => 'Une catégorie ne peut pas être son propre parent.',
            'image.max' => 'L\'image ne doit pas dépasser 2 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-génération du slug si vide
        if ($this->filled('name') && !$this->filled('slug')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->input('name'))
            ]);
        }

        // Conversion du booléen
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}