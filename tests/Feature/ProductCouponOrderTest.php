<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Livewire\Livewire;

function submitOrderWithCoupon(Product $product, string $couponCode)
{
    return Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->set('couponCode', $couponCode)
        ->call('applyCoupon');
}

test('a valid fixed coupon reduces the total and increments times_used', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    $coupon = Coupon::factory()->fixed(1500)->create(['code' => 'SAVE15']);

    submitOrderWithCoupon($product, 'SAVE15')
        ->assertSet('appliedCoupon.id', $coupon->id)
        ->call('submitOrder')
        ->assertRedirect();

    $order = Order::first();

    expect($order->coupon_id)->toBe($coupon->id);
    expect($order->coupon_code_snapshot)->toBe('SAVE15');
    expect($order->discount_cents_snapshot)->toBe(1500);
    expect($order->total_cents_snapshot)->toBe(8500);
    expect($coupon->fresh()->times_used)->toBe(1);
});

test('a valid percentage coupon reduces the total correctly', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    $coupon = Coupon::factory()->percentage(10)->create(['code' => 'TENOFF']);

    submitOrderWithCoupon($product, 'TENOFF')->call('submitOrder');

    $order = Order::first();

    expect($order->discount_cents_snapshot)->toBe(1000);
    expect($order->total_cents_snapshot)->toBe(9000);
});

test('an expired coupon is rejected', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    Coupon::factory()->expired()->create(['code' => 'EXPIRED']);

    submitOrderWithCoupon($product, 'EXPIRED')
        ->assertSet('appliedCoupon', null)
        ->assertSet('couponError', fn ($error) => $error !== null);
});

test('a coupon that has not started yet is rejected', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    Coupon::factory()->notStartedYet()->create(['code' => 'FUTURE']);

    submitOrderWithCoupon($product, 'FUTURE')
        ->assertSet('appliedCoupon', null);
});

test('a coupon at its usage limit is rejected', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    Coupon::factory()->maxedOut()->create(['code' => 'MAXED']);

    submitOrderWithCoupon($product, 'MAXED')
        ->assertSet('appliedCoupon', null);
});

test('a coupon below the minimum order amount is rejected', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 1000]);
    Coupon::factory()->create(['code' => 'BIGORDER', 'min_order_cents' => 5000]);

    submitOrderWithCoupon($product, 'BIGORDER')
        ->assertSet('appliedCoupon', null);
});

test('an unknown coupon code is rejected', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);

    submitOrderWithCoupon($product, 'DOESNOTEXIST')
        ->assertSet('appliedCoupon', null);
});

test('an order placed without a coupon has zero discount', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->call('submitOrder');

    $order = Order::first();

    expect($order->coupon_id)->toBeNull();
    expect($order->discount_cents_snapshot)->toBe(0);
    expect($order->total_cents_snapshot)->toBe(10000);
});

test('only one of two concurrent orders can redeem a single-use coupon', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    $coupon = Coupon::factory()->create(['code' => 'ONEUSE', 'max_uses' => 1]);

    submitOrderWithCoupon($product, 'ONEUSE')->call('submitOrder');
    submitOrderWithCoupon($product, 'ONEUSE')->call('submitOrder');

    expect($coupon->fresh()->times_used)->toBe(1);
    expect(Order::whereNotNull('coupon_id')->count())->toBe(1);
});

test('two checkouts that both applied a single-use coupon before either submitted yield one discounted order', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 10000]);
    $coupon = Coupon::factory()->fixed(1500)->create(['code' => 'ONEUSE', 'max_uses' => 1]);

    // Both components read times_used = 0 before either checkout submits.
    $first = submitOrderWithCoupon($product, 'ONEUSE')->assertSet('appliedCoupon.id', $coupon->id);
    $second = submitOrderWithCoupon($product, 'ONEUSE')->assertSet('appliedCoupon.id', $coupon->id);

    $first->call('submitOrder');
    $second->call('submitOrder')->assertHasErrors('couponCode');

    expect($coupon->fresh()->times_used)->toBe(1);
    expect(Order::whereNotNull('coupon_id')->count())->toBe(1);
    expect(Order::where('discount_cents_snapshot', '>', 0)->count())->toBe(1);
});
