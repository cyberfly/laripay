<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use Livewire\Livewire;

test('ordering a physical product requires shipping fields', function () {
    $product = Product::factory()->physical()->create(['is_active' => true]);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->call('submitOrder')
        ->assertHasErrors(['shippingAddressLine1', 'city', 'state', 'postcode', 'country']);
});

test('submitting a valid order for a physical product creates a snapshot and redirects to payment', function () {
    $product = Product::factory()->physical()->create(['is_active' => true, 'name' => 'Wireless Mouse']);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->set('shippingAddressLine1', '123 Main St')
        ->set('city', 'Kuala Lumpur')
        ->set('state', 'WP')
        ->set('postcode', '50000')
        ->set('country', 'Malaysia')
        ->call('submitOrder')
        ->assertRedirect();

    $order = Order::first();

    expect($order)->not->toBeNull();
    expect($order->product_name_snapshot)->toBe('Wireless Mouse');
    expect($order->price_cents_snapshot)->toBe($product->price_cents);
    expect($order->payment_status)->toBe(PaymentStatus::Pending);
    expect($order->customer_email)->toBe('jane@example.com');
});

test('ordering a digital product does not require shipping fields', function () {
    $product = Product::factory()->digital()->create(['is_active' => true]);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->call('submitOrder')
        ->assertHasNoErrors();

    expect(Order::count())->toBe(1);
});
