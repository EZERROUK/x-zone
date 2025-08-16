<?php

namespace Database\Factories;

use App\Models\CategoryAttribute;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryAttributeFactory extends Factory
{
    protected $model = CategoryAttribute::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $type = $this->faker->randomElement(['text', 'number', 'decimal', 'boolean', 'select', 'date']);
        
        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'type' => $type,
            'description' => $this->faker->optional(0.6)->sentence(),
            'unit' => $this->getUnitForType($type),
            'default_value' => $this->faker->optional(0.3)->word(),
            'validation_rules' => $this->getValidationRulesForType($type),
            'is_required' => $this->faker->boolean(30),
            'is_filterable' => $this->faker->boolean(70),
            'is_searchable' => $this->faker->boolean(50),
            'show_in_listing' => $this->faker->boolean(40),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => $this->faker->boolean(95),
        ];
    }

    private function getUnitForType(string $type): ?string
    {
        return match ($type) {
            'number' => $this->faker->randomElement(['GB', 'MHz', 'W', 'mm', null]),
            'decimal' => $this->faker->randomElement(['kg', 'cm', 'L', '€', null]),
            default => null,
        };
    }

    private function getValidationRulesForType(string $type): ?array
    {
        return match ($type) {
            'number' => ['min' => 0, 'max' => 9999],
            'decimal' => ['min' => 0.0, 'max' => 999.99],
            'text' => ['max_length' => 255],
            default => null,
        };
    }

    public function forTechnology(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Capacité', 'Vitesse', 'Type', 'Interface', 'Consommation']),
            'type' => $this->faker->randomElement(['number', 'select', 'text']),
            'unit' => $this->faker->randomElement(['GB', 'MHz', 'W', 'SATA', 'USB']),
            'is_filterable' => true,
            'is_searchable' => true,
        ]);
    }

    public function selectType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'is_filterable' => true,
        ]);
    }
}