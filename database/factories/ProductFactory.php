<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price_cents' => fake()->numberBetween(1000, 50000),
            'currency' => 'MYR',
            'type' => ProductType::Physical,
            'images' => [],
            'digital_file_path' => null,
            'digital_access_url' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is a physical product.
     */
    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Physical,
            'digital_file_path' => null,
            'digital_access_url' => null,
        ]);
    }

    /**
     * Indicate that the product is a digital product.
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Digital,
            'digital_access_url' => fake()->url(),
        ]);
    }
}
