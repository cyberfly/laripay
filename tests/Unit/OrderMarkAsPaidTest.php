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
