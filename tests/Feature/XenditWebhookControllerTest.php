<?php

use App\Enums\PaymentStatus;
use App\Models\Order;

beforeEach(function () {
    config([
        'services.xendit.callback_token' => 'test-callback-token',
    ]);
});

test('webhook marks the order as paid for a valid PAID payload', function () {
    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123', 'total_cents_snapshot' => 150000]);

    $response = $this->postJson('/webhooks/xendit', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
        'amount' => 1500,
    ], ['x-callback-token' => 'test-callback-token']);

    $response->assertNoContent();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

test('webhook rejects a PAID payload whose amount does not match the order total', function () {
    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123', 'total_cents_snapshot' => 150000]);

    $response = $this->postJson('/webhooks/xendit', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
        'amount' => 1,
    ], ['x-callback-token' => 'test-callback-token']);

    $response->assertStatus(422);
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('webhook fails closed with 403 when no callback token is configured', function () {
    config(['services.xendit.callback_token' => null]);

    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $response = $this->postJson('/webhooks/xendit', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
    ]);

    $response->assertForbidden();
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('webhook rejects an invalid callback token', function () {
    $order = Order::factory()->pending()->create(['xendit_invoice_id' => 'inv_123']);

    $response = $this->postJson('/webhooks/xendit', [
        'id' => 'inv_123',
        'external_id' => $order->order_number,
        'status' => 'PAID',
    ], ['x-callback-token' => 'wrong-token']);

    $response->assertForbidden();
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('webhook returns 404 for an unknown order', function () {
    $response = $this->postJson('/webhooks/xendit', [
        'id' => 'inv_unknown',
        'external_id' => 'ORD-UNKNOWN',
        'status' => 'PAID',
    ], ['x-callback-token' => 'test-callback-token']);

    $response->assertNotFound();
});
