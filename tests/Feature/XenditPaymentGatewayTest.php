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
    expect($order->xendit_invoice_url)->toBe('https://checkout.xendit.co/web/inv_123');
    expect($order->xendit_expires_at)->not->toBeNull();
});

test('initiate reuses the existing invoice while it is still live', function () {
    $order = Order::factory()->pending()->create([
        'xendit_invoice_id' => 'inv_123',
        'xendit_invoice_url' => 'https://checkout.xendit.co/web/inv_123',
        'xendit_expires_at' => now()->addHour(),
    ]);

    Http::fake();

    $result = (new XenditPaymentGateway)->initiate($order);

    expect($result->redirectUrl)->toBe('https://checkout.xendit.co/web/inv_123');
    Http::assertNothingSent();
});

test('initiate creates a new invoice once the previous one has expired', function () {
    $order = Order::factory()->pending()->create([
        'xendit_invoice_id' => 'inv_old',
        'xendit_invoice_url' => 'https://checkout.xendit.co/web/inv_old',
        'xendit_expires_at' => now()->subHour(),
    ]);

    Http::fake([
        'api.xendit.co/v2/invoices' => Http::response([
            'id' => 'inv_new',
            'invoice_url' => 'https://checkout.xendit.co/web/inv_new',
            'expiry_date' => now()->addDay()->toIso8601String(),
        ], 200),
    ]);

    $result = (new XenditPaymentGateway)->initiate($order);

    expect($result->redirectUrl)->toBe('https://checkout.xendit.co/web/inv_new');
    expect($order->fresh()->xendit_invoice_id)->toBe('inv_new');
});

test('handleCallback marks the order as paid on a valid PAID payload', function () {
    Event::fake([OrderPaid::class]);

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123', 'total_cents_snapshot' => 150000]);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
        'amount' => 1500,
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    (new XenditPaymentGateway)->handleCallback($request);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->payment_reference)->toBe('inv_123');
    expect($order->paid_at)->not->toBeNull();

    Event::assertDispatched(OrderPaid::class);
});

test('handleCallback rejects a PAID payload whose amount does not match the order total', function () {
    Event::fake([OrderPaid::class]);

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123', 'total_cents_snapshot' => 150000]);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
        'amount' => 1, // far below the order's actual total of 1500.00
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    expect(fn () => (new XenditPaymentGateway)->handleCallback($request))
        ->toThrow(HttpException::class);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
    Event::assertNotDispatched(OrderPaid::class);
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

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123', 'total_cents_snapshot' => 150000]);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
        'amount' => 1500,
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

test('handleCallback fails closed with 403 when no callback token is configured', function () {
    config(['services.xendit.callback_token' => null]);

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
    ]);
    // Deliberately no x-callback-token header, matching the misconfigured-token case.

    (new XenditPaymentGateway)->handleCallback($request);
})->throws(HttpException::class);

test('handleCallback does not touch manual-payment orders when the payload carries no invoice id', function () {
    Event::fake([OrderPaid::class]);

    $manualOrder = Order::factory()->pending()->create([
        'xendit_invoice_id' => null,
        'total_cents_snapshot' => 150000,
    ]);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'external_id' => 'ORD-UNKNOWN',
        'status' => 'PAID',
        'amount' => 1500,
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    expect(fn () => (new XenditPaymentGateway)->handleCallback($request))
        ->toThrow(NotFoundHttpException::class);

    expect($manualOrder->fresh()->payment_status)->toBe(PaymentStatus::Pending);
    Event::assertNotDispatched(OrderPaid::class);
});

test('handleCallback does not fail an unrelated order on an EXPIRED payload with no invoice id', function () {
    $manualOrder = Order::factory()->pending()->create(['xendit_invoice_id' => null]);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'external_id' => 'ORD-UNKNOWN',
        'status' => 'EXPIRED',
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    expect(fn () => (new XenditPaymentGateway)->handleCallback($request))
        ->toThrow(NotFoundHttpException::class);

    expect($manualOrder->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('handleCallback aborts with 422 when the payload identifies no order at all', function () {
    $manualOrder = Order::factory()->pending()->create(['xendit_invoice_id' => null]);

    $request = Request::create('/webhooks/xendit', 'POST', ['status' => 'EXPIRED']);
    $request->headers->set('x-callback-token', 'test-callback-token');

    expect(fn () => (new XenditPaymentGateway)->handleCallback($request))
        ->toThrow(HttpException::class);

    expect($manualOrder->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('handleCallback still resolves the order by invoice id alone', function () {
    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123', 'total_cents_snapshot' => 150000]);

    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_123',
        'status' => 'PAID',
        'amount' => 1500,
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    (new XenditPaymentGateway)->handleCallback($request);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

test('handleCallback aborts with 404 when the order cannot be found', function () {
    $request = Request::create('/webhooks/xendit', 'POST', [
        'id' => 'inv_unknown',
        'external_id' => 'ORD-UNKNOWN',
        'status' => 'PAID',
    ]);
    $request->headers->set('x-callback-token', 'test-callback-token');

    (new XenditPaymentGateway)->handleCallback($request);
})->throws(NotFoundHttpException::class);
