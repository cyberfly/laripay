<?php

use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can see aggregated customers list', function () {
    Order::factory()->create(['customer_name' => 'Alice', 'customer_email' => 'alice@example.com']);
    Order::factory()->create(['customer_name' => 'Alice', 'customer_email' => 'alice@example.com']);
    Order::factory()->create(['customer_name' => 'Bob', 'customer_email' => 'bob@example.com']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.customers.index')
        ->assertSee('Alice')
        ->assertSee('alice@example.com')
        ->assertSee('Bob')
        ->assertSee('bob@example.com');
});

test('admin can filter customers by search', function () {
    Order::factory()->create(['customer_name' => 'Alice', 'customer_email' => 'alice@example.com']);
    Order::factory()->create(['customer_name' => 'Bob', 'customer_email' => 'bob@example.com']);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.customers.index')
        ->set('search', 'alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

test('admin can view a customer detail page with their orders', function () {
    $orders = Order::factory()->count(2)->create([
        'customer_name' => 'Alice',
        'customer_email' => 'alice@example.com',
    ]);

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.customers.show', ['email' => 'alice@example.com'])
        ->assertSee('Alice')
        ->assertSee($orders->first()->order_number)
        ->assertSee($orders->last()->order_number);
});

test('customer detail page 404s for an unknown email', function () {
    $this->actingAs($this->admin);

    $this->get(route('admin.customers.show', 'nobody@example.com'))
        ->assertNotFound();
});
