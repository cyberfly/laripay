<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('COUPON###'),
            'description' => fake()->sentence(),
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 1000,
            'min_order_cents' => 0,
            'max_uses' => null,
            'times_used' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the coupon is a percentage discount.
     */
    public function percentage(int $value = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => DiscountType::Percentage,
            'discount_value' => $value,
        ]);
    }

    /**
     * Indicate that the coupon is a fixed-amount discount.
     */
    public function fixed(int $cents = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => DiscountType::Fixed,
            'discount_value' => $cents,
        ]);
    }

    /**
     * Indicate that the coupon has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the coupon has not started yet.
     */
    public function notStartedYet(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the coupon has reached its usage limit.
     */
    public function maxedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 1,
            'times_used' => 1,
        ]);
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
