<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'sku',
        'price_modifier',
        'stock_quantity',
        'attributes',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price_modifier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Méthodes utilitaires
    public function getFinalPrice(): float
    {
        return $this->product->price + $this->price_modifier;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }
}