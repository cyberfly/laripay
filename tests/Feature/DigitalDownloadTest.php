<?php

use App\Models\Order;
use App\Models\Product;

test('download is rejected for an unpaid order', function () {
    $product = Product::factory()->digital()->create();
    $order = Order::factory()->forProduct($product)->pending()->create();

    $response = $this->get(route('shop.downloads.show', [$order, $order->digital_access_token]));

    $response->assertForbidden();
});

test('download is rejected for an invalid token', function () {
    $product = Product::factory()->digital()->create();
    $order = Order::factory()->forProduct($product)->paid()->create();

    $response = $this->get(route('shop.downloads.show', [$order, 'wrong-token']));

    $response->assertNotFound();
});

test('download is accepted for a paid digital order', function () {
    $product = Product::factory()->digital()->create(['digital_access_url' => 'https://files.example.com/file.zip']);
    $order = Order::factory()->forProduct($product)->paid()->create();

    $response = $this->get(route('shop.downloads.show', [$order, $order->digital_access_token]));

    $response->assertRedirect('https://files.example.com/file.zip');
});

test('download is rejected for a physical order', function () {
    $product = Product::factory()->physical()->create();
    $order = Order::factory()->forProduct($product)->paid()->create();

    $response = $this->get(route('shop.downloads.show', [$order, $order->digital_access_token]));

    $response->assertNotFound();
});
