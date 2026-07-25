<?php

use App\Payments\ManualPaymentGateway;
use App\Payments\XenditPaymentGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    */

    'default' => env('PAYMENT_GATEWAY', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */

    'gateways' => [
        'manual' => ManualPaymentGateway::class,
        'xendit' => XenditPaymentGateway::class,
    ],

];
