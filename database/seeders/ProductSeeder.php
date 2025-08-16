<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Category, Product, Brand, Currency, TaxRate, ProductAttributeValue};
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // S'assurer qu'on a les données de base
        $categories = Category::whereNotNull('parent_id')->get(); // Seulement les enfants
        $brands = Brand::all();
        $currency = Currency::first();
        $taxRate = TaxRate::first();

        if ($categories->isEmpty() || $brands->isEmpty() || !$currency || !$taxRate) {
            $this->command->warn('Veuillez d\'abord exécuter les seeders pour Categories, Brands, Currencies et TaxRates');
            return;
        }

        // Créer des produits pour chaque catégorie
        foreach ($categories as $category) {
            $this->createProductsForCategory($category, $brands, $currency, $taxRate);
        }
    }

    private function createProductsForCategory($category, $brands, $currency, $taxRate): void
    {
        $productCount = rand(5, 15);
        
        for ($i = 0; $i < $productCount; $i++) {
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brands->random()->id,
                'currency_code' => $currency->code,
                'tax_rate_id' => $taxRate->id,
            ]);

            // Ajouter des valeurs d'attributs aléatoires
            $this->addAttributeValues($product, $category);
        }
    }

    private function addAttributeValues(Product $product, Category $category): void
    {
        $attributes = $category->attributes()->with('options')->get();

        foreach ($attributes as $attribute) {
            $value = $this->generateValueForAttribute($attribute);
            
            if ($value !== null) {
                ProductAttributeValue::create([
                    'product_id' => $product->id,
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }
        }
    }

    private function generateValueForAttribute($attribute): ?string
    {
        // 70% de chance d'avoir une valeur (pour simuler des données incomplètes)
        if (rand(1, 100) > 70) {
            return null;
        }

        return match ($attribute->type) {
            'text', 'textarea' => fake()->words(rand(1, 3), true),
            'number' => (string) rand(1, 1000),
            'decimal' => (string) round(rand(1, 1000) / 10, 2),
            'boolean' => rand(0, 1) ? '1' : '0',
            'date' => now()->subDays(rand(0, 365))->toDateString(),
            'url' => fake()->url(),
            'email' => fake()->email(),
            'select' => $attribute->options->isNotEmpty() 
                ? $attribute->options->random()->value 
                : null,
            'multiselect' => $attribute->options->isNotEmpty()
                ? json_encode($attribute->options->random(rand(1, min(3, $attribute->options->count())))->pluck('value')->toArray())
                : null,
            default => fake()->word(),
        };
    }
}