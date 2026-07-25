<?php

use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Payments\PaymentInitiationResult;
use App\Payments\XenditPaymentGateway;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    config([
        'services.xendit.secret_key' => 'test-secret-key',
        'services.xendit.callback_token' => 'test-callback-token',
    ]);
});

test('initiate creates a xendit invoice and returns its redirect url', function () {
    $order = Order::factory()->create([
        'total_cents_snapshot' => 150000,
        'currency_snapshot' => 'MYR',
    ]);

    Http::fake([
        'api.xendit.co/v2/invoices' => Http::response([
            'id' => 'inv_123',
            'invoice_url' => 'https://checkout.xendit.co/web/inv_123',
            'expiry_date' => '2026-07-26T00:00:00.000Z',
        ], 200),
    ]);

    $result = (new XenditPaymentGateway)->initiate($order);

    expect($result)->toBeInstanceOf(PaymentInitiationResult::class);
    expect($result->redirectUrl)->toBe('https://checkout.xendit.co/web/inv_123');

    Http::assertSent(function (ClientRequest $request) use ($order) {
        return $request->url() === 'https://api.xendit.co/v2/invoices'
            && $request['external_id'] === $order->order_number
            && $request['amount'] === 1500.0
            && $request['currency'] === 'MYR';
    });

    $order->refresh();
    expect($order->xendit_invoice_id)->toBe('inv_123');
    expect($order->xendit_expires_at)->not->toBeNull();
});

test('handleCallback marks the order as paid on a valid PAID payload', function () {
    Event::fake([OrderPaid::class]);

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    (new XenditPaymentGateway)->handleCallback($request);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->payment_reference)->toBe('inv_123');
    expect($order->paid_at)->not->toBeNull();

    Event::assertDispatched(OrderPaid::class);
});

test('handleCallback marks the order as failed on an EXPIRED payload', function () {
    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'EXPIRED',
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    (new XenditPaymentGateway)->handleCallback($request);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Failed);
});

test('handleCallback is idempotent for a repeated PAID payload', function () {
    Event::fake([OrderPaid::class]);

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    $gateway = new XenditPaymentGateway;
    $gateway->handleCallback($request);
    $gateway->handleCallback($request);

    Event::assertDispatchedTimes(OrderPaid::class, 1);
});

test('handleCallback aborts with 403 for an invalid token', function () {
    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
    ]);
    $request->headers->set('x-callback-token', 'wrong-token');

    (new XenditPaymentGateway)->handleCallback($request);
})->throws(HttpException::class);

test('handleCallback aborts with 404 when the order cannot be found', function () {
    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_unknown',
        'external_id' => 'ORD-UNKNOWN',
        'status' => 'PAID',
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    (new XenditPaymentGateway)->handleCallback($request);
})->throws(NotFoundHttpException::class);
