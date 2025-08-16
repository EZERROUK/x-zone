<?php
namespace Database\Factories;

use App\Models\Product;
use App\Models\{Brand, Category, Currency, TaxRate};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $type = $this->faker->randomElement(['physical', 'digital', 'service']);
        
        return [
            'id' => Str::uuid()->toString(),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'model' => $this->faker->optional()->bothify('Model-##??'),
            'sku' => strtoupper($this->faker->bothify('SKU-???-#####')),
            'description' => $this->faker->paragraph(),
            'meta_title' => $this->faker->optional()->sentence(6),
            'meta_description' => $this->faker->optional()->sentence(12),
            
            // E-commerce fields
            'type' => $type,
            'price' => $this->faker->randomFloat(2, 10, 2000),
            'compare_at_price' => $this->faker->optional(0.3)->randomFloat(2, 50, 2500),
            'cost_price' => $this->faker->optional(0.7)->randomFloat(2, 5, 1000),
            
            // Stock
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'track_inventory' => $this->faker->boolean(80),
            'low_stock_threshold' => $this->faker->numberBetween(5, 20),
            'allow_backorder' => $this->faker->boolean(30),
            
            // Physical product dimensions
            'weight' => $type === 'physical' ? $this->faker->randomFloat(2, 0.1, 50) : null,
            'length' => $type === 'physical' ? $this->faker->randomFloat(2, 5, 100) : null,
            'width' => $type === 'physical' ? $this->faker->randomFloat(2, 5, 100) : null,
            'height' => $type === 'physical' ? $this->faker->randomFloat(2, 1, 50) : null,
            
            // Digital product fields
            'download_url' => $type === 'digital' ? $this->faker->url() : null,
            'download_limit' => $type === 'digital' ? $this->faker->numberBetween(1, 10) : null,
            'download_expiry_days' => $type === 'digital' ? $this->faker->numberBetween(30, 365) : null,
            
            // Visibility
            'visibility' => $this->faker->randomElement(['public', 'private', 'hidden']),
            'is_featured' => $this->faker->boolean(20),
            'is_active' => $this->faker->boolean(90),
            'available_from' => $this->faker->optional(0.2)->dateTimeBetween('-1 month', '+1 month'),
            'available_until' => $this->faker->optional(0.1)->dateTimeBetween('+1 month', '+1 year'),
            
            // Relations
            'currency_code' => 'MAD',
            'tax_rate_id' => TaxRate::factory(),
        ];
    }

    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'physical',
            'weight' => $this->faker->randomFloat(2, 0.1, 50),
            'length' => $this->faker->randomFloat(2, 5, 100),
            'width' => $this->faker->randomFloat(2, 5, 100),
            'height' => $this->faker->randomFloat(2, 1, 50),
            'download_url' => null,
            'download_limit' => null,
            'download_expiry_days' => null,
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'digital',
            'weight' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'download_url' => $this->faker->url(),
            'download_limit' => $this->faker->numberBetween(1, 10),
            'download_expiry_days' => $this->faker->numberBetween(30, 365),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'visibility' => 'public',
            'is_active' => true,
        ]);
    }

    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? 100;
            return [
                'compare_at_price' => $price * $this->faker->randomFloat(2, 1.2, 2.0),
            ];
        });
    }
}