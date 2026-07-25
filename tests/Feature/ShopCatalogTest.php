<?php

use App\Models\Product;

test('catalog shows only active products', function () {
    $active = Product::factory()->create(['is_active' => true]);
    $inactive = Product::factory()->create(['is_active' => false]);

    $response = $this->get(route('shop.index'));

    $response->assertOk();
    $response->assertSee($active->name);
    $response->assertDontSee($inactive->name);
});

test('inactive product landing page returns 404', function () {
    $product = Product::factory()->create(['is_active' => false]);

    $response = $this->get(route('shop.products.show', $product));

    $response->assertNotFound();
});

test('active product landing page is viewable', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $response = $this->get(route('shop.products.show', $product));

    $response->assertOk();
    $response->assertSee($product->name);
});
