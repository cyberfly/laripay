<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\PaymentInitiationResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function initiate(Order $order): PaymentInitiationResult;

    public function handleCallback(Request $request): void;
}
