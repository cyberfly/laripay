<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected away from admin routes', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('non-admin users get a 403 from admin routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('admins can access admin routes', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

test('admin dashboard shows the orders chart data', function () {
    $admin = User::factory()->admin()->create();
    Order::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Orders (last 14 days)');
});

test('non-admin users cannot invoke admin Livewire component actions', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $coupon = Coupon::factory()->create();
    $order = Order::factory()->pending()->create();

    $this->actingAs($user);

    Livewire::test('pages::admin.dashboard')
        ->assertForbidden();

    Livewire::test('pages::admin.products.index')
        ->assertForbidden();

    Livewire::test('pages::admin.products.create')
        ->assertForbidden();

    Livewire::test('pages::admin.products.edit', ['product' => $product])
        ->assertForbidden();

    Livewire::test('pages::admin.coupons.index')
        ->assertForbidden();

    Livewire::test('pages::admin.coupons.create')
        ->assertForbidden();

    Livewire::test('pages::admin.coupons.edit', ['coupon' => $coupon])
        ->assertForbidden();

    Livewire::test('pages::admin.orders.index')
        ->assertForbidden();

    Livewire::test('pages::admin.orders.show', ['order' => $order])
        ->assertForbidden();

    Livewire::test('pages::admin.customers.index')
        ->assertForbidden();

    Livewire::test('pages::admin.customers.show', ['email' => $order->customer_email])
        ->assertForbidden();

    expect(Product::find($product->id))->not->toBeNull();
    expect(Coupon::find($coupon->id))->not->toBeNull();
});

test('a user whose admin flag is revoked cannot keep using an existing component instance', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.products.index');

    $admin->forceFill(['is_admin' => false])->save();

    $component->call('delete', $product)->assertForbidden();

    expect(Product::find($product->id))->not->toBeNull();
});
