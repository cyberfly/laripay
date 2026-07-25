<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use Illuminate\Support\Facades\Log;

class SendOrderPaidNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        Log::info('Order paid.', ['order_number' => $event->order->order_number]);
    }
}
