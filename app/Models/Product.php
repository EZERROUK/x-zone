<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasUuids, HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'brand_id', 'name', 'model', 'sku', 'description', 'price', 'stock_quantity',
        'currency_code', 'tax_rate_id', 'category_id', 'image_main', 'is_active',
        // Nouveaux champs e-commerce
        'slug', 'meta_title', 'meta_description', 'type', 'compare_at_price', 'cost_price',
        'weight', 'length', 'width', 'height', 'track_inventory', 'low_stock_threshold',
        'allow_backorder', 'is_featured', 'visibility', 'available_from', 'available_until',
        'download_url', 'download_limit', 'download_expiry_days'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
        'allow_backorder' => 'boolean',
        'is_featured' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations principales
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('is_primary', 'desc');
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Relations pour le système d'attributs flexibles
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    // Relation many-to-many avec categories (pour catégories multiples)
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    // Compatibilités
    public function compatibleWith(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_compatibilities', 'product_id', 'compatible_with_id')
            ->withPivot('direction', 'note')
            ->withTimestamps();
    }

    public function isCompatibleWith(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_compatibilities', 'compatible_with_id', 'product_id')
            ->withPivot('direction', 'note')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('visibility', 'public')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                  ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                  ->orWhere('available_until', '>=', now());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    // Méthodes pour le système d'attributs flexibles
    public function getCustomAttributeValue(string $attributeSlug)
    {
        $value = $this->attributeValues()
            ->whereHas('attribute', function ($q) use ($attributeSlug) {
                $q->where('slug', $attributeSlug);
            })
            ->first();

        return $value ? $value->getTypedValueAttribute() : null;
    }

    public function setCustomAttributeValue(string $attributeSlug, $value): void
    {
        $attribute = CategoryAttribute::where('slug', $attributeSlug)
            ->whereHas('category', function ($q) {
                $q->where('id', $this->category_id);
            })
            ->first();

        if (!$attribute) {
            return;
        }

        // Convertir la valeur selon le type
        $convertedValue = match ($attribute->type) {
            'multiselect' => is_array($value) ? json_encode($value) : $value,
            'boolean' => $value ? '1' : '0',
            'date' => $value instanceof \Carbon\Carbon ? $value->toDateString() : $value,
            default => (string) $value,
        };

        $this->attributeValues()->updateOrCreate(
            ['attribute_id' => $attribute->id],
            ['value' => $convertedValue]
        );
    }

    public function getAttributesForCategory(): \Illuminate\Support\Collection
    {
        if (!$this->category) {
            return collect();
        }

        return $this->category->attributes()
            ->active()
            ->with(['options' => function ($q) {
                $q->active()->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($attribute) {
                $value = $this->attributeValues()
                    ->where('attribute_id', $attribute->id)
                    ->first();

                $attribute->current_value = $value ? $value->getTypedValueAttribute() : null;
                $attribute->formatted_value = $value ? $value->getFormattedValueAttribute() : null;
                
                return $attribute;
            });
    }

    // Méthodes e-commerce
    public function getImageUrl(): ?string
    {
        return $this->image_main ? Storage::url($this->image_main) : null;
    }

    public function getPrimaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first();
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' ' . $this->currency_code;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentage(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 1);
    }

    public function isInStock(): bool
    {
        return !$this->track_inventory || $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->visibility !== 'public') {
            return false;
        }

        $now = now();

        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }

        if ($this->available_until && $this->available_until->isPast()) {
            return false;
        }

        return true;
    }

    public function canBeOrdered(): bool
    {
        return $this->isAvailable() && ($this->isInStock() || $this->allow_backorder);
    }

    // Boot events
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
            }
        });

        static::updating(function (Product $product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
            }
        });
    }

    // Logs d'activité (Spatie)
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('product')
            ->logAll()
            ->logOnlyDirty()
            ->logExcept(['updated_at'])
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Product has been {$eventName}");
    }
}