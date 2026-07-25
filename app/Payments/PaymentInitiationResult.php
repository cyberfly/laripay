<?php

namespace App\Payments;

class PaymentInitiationResult
{
    public function __construct(public string $redirectUrl)
    {
        //
    }
}
