<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryAttribute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'type', 'description',
        'validation_rules', 'unit', 'default_value', 'is_required',
        'is_filterable', 'is_searchable', 'show_in_listing',
        'sort_order', 'is_active'
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_searchable' => 'boolean',
        'show_in_listing' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id');
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    // Méthodes utilitaires
    public function getValidationRulesArray(): array
    {
        $rules = ['nullable'];
        
        if ($this->is_required) {
            $rules = ['required'];
        }

        switch ($this->type) {
            case 'number':
                $rules[] = 'integer';
                break;
            case 'decimal':
                $rules[] = 'numeric';
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'select':
            case 'multiselect':
                $validOptions = $this->options->pluck('value')->toArray();
                if (!empty($validOptions)) {
                    $rules[] = 'in:' . implode(',', $validOptions);
                }
                break;
            default:
                $rules[] = 'string';
                if ($this->validation_rules && isset($this->validation_rules['max_length'])) {
                    $rules[] = 'max:' . $this->validation_rules['max_length'];
                }
        }

        // Ajouter les règles personnalisées
        if ($this->validation_rules) {
            if (isset($this->validation_rules['min'])) {
                $rules[] = 'min:' . $this->validation_rules['min'];
            }
            if (isset($this->validation_rules['max'])) {
                $rules[] = 'max:' . $this->validation_rules['max'];
            }
        }

        return $rules;
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multiselect']);
    }
}