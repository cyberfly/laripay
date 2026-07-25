<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can mark an order as paid and it fires OrderPaid', function () {
    Event::fake(OrderPaid::class);

    $order = Order::factory()->pending()->create();

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.orders.show', ['order' => $order])
        ->call('markAsPaid');

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
    Event::assertDispatched(OrderPaid::class);
});

test('admin can update fulfillment status and tracking number', function () {
    $order = Order::factory()->forProduct(
        Product::factory()->physical()->create()
    )->paid()->create();

    $this->actingAs($this->admin);

    Livewire::test('pages::admin.orders.show', ['order' => $order])
        ->set('status', OrderStatus::Shipped->value)
        ->set('trackingNumber', 'TRACK123')
        ->call('updateFulfillment');

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Shipped);
    expect($order->tracking_number)->toBe('TRACK123');
    expect($order->shipped_at)->not->toBeNull();
});
