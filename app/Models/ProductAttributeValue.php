<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected $fillable = [
        'product_id', 'attribute_id', 'value'
    ];

    protected $casts = [
        'value' => 'string', // On stocke tout en string, conversion à la volée
    ];

    // Relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CategoryAttribute::class, 'attribute_id');
    }

    // Accessors pour conversion de type
    public function getTypedValueAttribute()
    {
        if (!$this->attribute) {
            return $this->value;
        }

        return match ($this->attribute->type) {
            'number' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'date' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            'multiselect' => $this->value ? json_decode($this->value, true) : [],
            default => $this->value,
        };
    }

    public function getFormattedValueAttribute(): string
    {
        $typedValue = $this->getTypedValueAttribute();
        
        if (is_null($typedValue)) {
            return '';
        }

        return match ($this->attribute->type) {
            'boolean' => $typedValue ? 'Oui' : 'Non',
            'decimal' => number_format($typedValue, 2) . ($this->attribute->unit ? ' ' . $this->attribute->unit : ''),
            'number' => number_format($typedValue) . ($this->attribute->unit ? ' ' . $this->attribute->unit : ''),
            'multiselect' => is_array($typedValue) ? implode(', ', $typedValue) : $typedValue,
            'date' => $typedValue instanceof \Carbon\Carbon ? $typedValue->format('d/m/Y') : $typedValue,
            default => (string) $typedValue . ($this->attribute->unit ? ' ' . $this->attribute->unit : ''),
        };
    }
}