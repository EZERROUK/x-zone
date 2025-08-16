<?php

namespace Database\Factories;

use App\Models\AttributeOption;
use App\Models\CategoryAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttributeOptionFactory extends Factory
{
    protected $model = AttributeOption::class;

    public function definition(): array
    {
        $label = $this->faker->word();
        
        return [
            'attribute_id' => CategoryAttribute::factory(),
            'label' => ucfirst($label),
            'value' => Str::slug($label),
            'color' => $this->faker->optional(0.3)->hexColor(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => $this->faker->boolean(95),
        ];
    }

    public function forColors(): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => $this->faker->randomElement(['Rouge', 'Bleu', 'Vert', 'Noir', 'Blanc']),
            'color' => $this->faker->hexColor(),
        ]);
    }

    public function forSizes(): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'color' => null,
        ]);
    }
}