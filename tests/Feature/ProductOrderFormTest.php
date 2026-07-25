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

test('order total scales with quantity', function () {
    $product = Product::factory()->digital()->create(['is_active' => true, 'price_cents' => 1000]);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('quantity', 3)
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->call('submitOrder')
        ->assertHasNoErrors();

    $order = Order::first();

    expect($order->quantity)->toBe(3);
    expect($order->total_cents_snapshot)->toBe(3000);
});

test('ordering requires a valid quantity', function () {
    $product = Product::factory()->digital()->create(['is_active' => true]);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->set('quantity', 0)
        ->set('customerName', 'Jane Doe')
        ->set('customerEmail', 'jane@example.com')
        ->call('submitOrder')
        ->assertHasErrors(['quantity']);
});

test('product description is rendered as formatted html on the storefront', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'description' => '<p>Great <strong>value</strong>.</p>',
    ]);

    Livewire::test('pages::shop.product-show', ['product' => $product])
        ->assertSeeHtml('<p>Great <strong>value</strong>.</p>');
});
