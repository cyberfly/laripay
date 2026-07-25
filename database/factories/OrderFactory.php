<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory();

        return [
            'product_id' => $product,
            'product_name_snapshot' => fake()->words(3, true),
            'price_cents_snapshot' => fake()->numberBetween(1000, 50000),
            'currency_snapshot' => 'MYR',
            'product_type_snapshot' => 'physical',
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'payment_method' => 'manual',
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ];
    }

    /**
     * Indicate that the order has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the order payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }

    /**
     * Assign the order to a specific product, snapshotting its fields.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'price_cents_snapshot' => $product->price_cents,
            'currency_snapshot' => $product->currency,
            'product_type_snapshot' => $product->type->value,
        ]);
    }
}
