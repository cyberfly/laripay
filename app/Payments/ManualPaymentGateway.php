<?php

namespace App\Payments;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;

class ManualPaymentGateway implements PaymentGateway
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        return new PaymentInitiationResult(
            redirectUrl: route('shop.orders.payment', $order),
        );
    }

    public function handleCallback(Request $request): void
    {
        // Manual payments are confirmed by an admin, not via callback.
    }
}
