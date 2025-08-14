<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        $id = $this->category?->id;   // null sur create
        $tenantId = auth()->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 
                'string', 
                'max:255',
                // Unique par tenant
                $id 
                    ? "unique:categories,slug,{$id},id,tenant_id,{$tenantId}"
                    : "unique:categories,slug,NULL,id,tenant_id,{$tenantId}"
            ],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Ce slug est déjà utilisé dans votre organisation.',
            'parent_id.exists' => 'La catégorie parente sélectionnée n\'existe pas.',
        ];
    }
}
