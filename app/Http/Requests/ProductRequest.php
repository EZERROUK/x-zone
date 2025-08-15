<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return match ($this->method()) {
            'POST'   => $this->user()->can('product_create'),
            'PATCH'  => $this->user()->can('product_edit'),
            'DELETE' => $this->user()->can('product_delete'),
            default  => true,
        };
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        $rules = [
            // Champs de base
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:100',
                $productId ? "unique:products,sku,{$productId}" : 'unique:products,sku'
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                $productId ? "unique:products,slug,{$productId}" : 'unique:products,slug'
            ],
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',

            // Relations
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'currency_code' => 'required|exists:currencies,code',
            'tax_rate_id' => 'required|exists:tax_rates,id',

            // Pricing
            'price' => 'required|numeric|min:0|max:999999.99',
            'compare_at_price' => 'nullable|numeric|min:0|max:999999.99|gt:price',
            'cost_price' => 'nullable|numeric|min:0|max:999999.99',

            // E-commerce
            'type' => 'required|in:physical,digital,service',
            'visibility' => 'required|in:public,private,hidden',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after:available_from',

            // Inventory
            'stock_quantity' => 'required|integer|min:0',
            'track_inventory' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'boolean',

            // Physical product fields
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',

            // Digital product fields
            'download_url' => 'nullable|url|max:500',
            'download_limit' => 'nullable|integer|min:1',
            'download_expiry_days' => 'nullable|integer|min:1',

            // Flags
            'is_featured' => 'boolean',
            'is_active' => 'boolean',

            // Images
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'primary_image_index' => 'nullable|integer|min:0',
            'deleted_image_ids' => 'nullable|array',
            'deleted_image_ids.*' => 'integer|exists:product_images,id',
            'restored_image_ids' => 'nullable|array',
            'restored_image_ids.*' => 'integer|exists:product_images,id',

            // Compatibilités
            'compatibilities' => 'nullable|array',
            'compatibilities.*.compatible_with_id' => 'required|exists:products,id',
            'compatibilities.*.direction' => 'nullable|in:bidirectional,uni',
            'compatibilities.*.note' => 'nullable|string|max:500',

            // Catégories multiples
            'additional_categories' => 'nullable|array',
            'additional_categories.*' => 'exists:categories,id',

            // Attributs personnalisés (validation dynamique)
            'attributes' => 'nullable|array',
        ];

        // Validation dynamique des attributs personnalisés
        if ($this->filled('category_id')) {
            $category = \App\Models\Category::with('attributes.options')->find($this->input('category_id'));
            
            if ($category) {
                $rules['attributes'] = ['nullable', 'array'];
                
                foreach ($category->attributes as $attribute) {
                    $attributeRules = $attribute->getValidationRulesArray();
                    $rules["attributes.{$attribute->slug}"] = $attributeRules;
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Ce SKU est déjà utilisé par un autre produit.',
            'slug.unique' => 'Ce slug est déjà utilisé par un autre produit.',
            'slug.regex' => 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets.',
            'compare_at_price.gt' => 'Le prix comparé doit être supérieur au prix de vente.',
            'available_until.after' => 'La date de fin de disponibilité doit être postérieure à la date de début.',
            'images.*.max' => 'Chaque image ne doit pas dépasser 5 MB.',
            'download_url.url' => 'L\'URL de téléchargement doit être valide.',
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

        // Conversion des booléens
        $booleanFields = [
            'is_active', 'is_featured', 'track_inventory', 'allow_backorder'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }
    }
}