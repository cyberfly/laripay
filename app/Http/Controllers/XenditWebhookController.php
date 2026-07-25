<?php

namespace App\Http\Controllers;

use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class XenditWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var PaymentGateway $gateway */
        $gateway = app(config('payments.gateways')['xendit']);

        $gateway->handleCallback($request);

        return response()->noContent();
    }
}
