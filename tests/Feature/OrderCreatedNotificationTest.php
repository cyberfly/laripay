<?php

use App\Events\OrderCreated;
use App\Listeners\SendOrderCreatedNotifications;
use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('creating an order fires OrderCreated', function () {
    Mail::fake();

    $order = Order::factory()->create();

    Mail::assertQueued(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->order->is($order)
        && $mail->hasTo($order->customer_email));
});

test('SendOrderCreatedNotifications sends confirmation to customer and alert to admins', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    User::factory()->create(['is_admin' => false, 'email' => 'not-admin@example.com']);

    $order = Order::factory()->create(['customer_email' => 'customer@example.com']);

    (new SendOrderCreatedNotifications)->handle(new OrderCreated($order));

    Mail::assertQueued(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->hasTo('customer@example.com'));

    Mail::assertQueued(NewOrderAdminNotification::class, function (NewOrderAdminNotification $mail) use ($admin): bool {
        return $mail->hasTo($admin->email) && ! $mail->hasTo('not-admin@example.com');
    });
});
