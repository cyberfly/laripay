<?php

use App\Models\Order;
use App\Payments\ManualPaymentGateway;
use App\Payments\PaymentInitiationResult;
use Illuminate\Http\Request;

test('initiate returns a redirect to the manual payment page', function () {
    $order = new Order;
    $order->order_number = 'ORD-TESTTESTTEST';

    $result = (new ManualPaymentGateway)->initiate($order);

    expect($result)->toBeInstanceOf(PaymentInitiationResult::class);
    expect($result->redirectUrl)->toBe(route('shop.orders.payment', $order));
});

test('handleCallback is a no-op', function () {
    $gateway = new ManualPaymentGateway;

    expect(fn () => $gateway->handleCallback(Request::create('/')))->not->toThrow(Throwable::class);
});
