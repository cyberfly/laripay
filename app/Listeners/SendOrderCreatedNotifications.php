<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderCreatedNotifications implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        Mail::to($event->order->customer_email)->send(new OrderConfirmationMail($event->order));

        $adminEmails = User::query()->where('is_admin', true)->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails)->send(new NewOrderAdminNotification($event->order));
        }
    }
}
