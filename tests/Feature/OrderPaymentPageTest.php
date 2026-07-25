<?php

use App\Models\Order;
use App\Models\Product;

test('payment page shows manual payment instructions for a pending order', function () {
    $product = Product::factory()->physical()->create();
    $order = Order::factory()->forProduct($product)->pending()->create();

    $response = $this->get(route('shop.orders.payment', $order));

    $response->assertOk();
    $response->assertSee($order->order_number);
    $response->assertDontSee(route('shop.downloads.show', [$order, $order->digital_access_token]));
});

test('payment page shows the download link once a digital order is paid', function () {
    $product = Product::factory()->digital()->create();
    $order = Order::factory()->forProduct($product)->paid()->create();

    $response = $this->get(route('shop.orders.payment', $order));

    $response->assertOk();
    $response->assertSee(route('shop.downloads.show', [$order, $order->digital_access_token]));
});
