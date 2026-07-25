<?php

use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/xendit', XenditWebhookController::class)->name('webhooks.xendit');
