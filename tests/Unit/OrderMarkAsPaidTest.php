<?php

use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('markAsPaid sets payment status, paid_at, confirmed by, and fires OrderPaid', function () {
    Event::fake(OrderPaid::class);

    $order = Order::factory()->pending()->create();
    $admin = User::factory()->admin()->create();

    $order->markAsPaid($admin);

    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->paid_at)->not->toBeNull();
    expect($order->payment_confirmed_by)->toBe($admin->id);

    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $event): bool => $event->order->is($order));
});

test('only one of two concurrent markAsPaid calls wins and OrderPaid fires once', function () {
    Event::fake(OrderPaid::class);

    $order = Order::factory()->pending()->create();

    // Two handlers that each read the order while it was still pending.
    $first = Order::find($order->id);
    $second = Order::find($order->id);

    expect($first->markAsPaid())->toBeTrue();
    expect($second->markAsPaid())->toBeFalse();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);

    Event::assertDispatchedTimes(OrderPaid::class, 1);
});
