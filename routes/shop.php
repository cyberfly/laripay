<?php

use App\Http\Controllers\DigitalDownloadController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::shop.index')->name('shop.index');
Route::livewire('products/{product:slug}', 'pages::shop.product-show')->name('shop.products.show');
Route::livewire('orders/{order:order_number}/payment', 'pages::shop.order-payment')->name('shop.orders.payment');
Route::get('downloads/{order:order_number}/{token}', DigitalDownloadController::class)->name('shop.downloads.show');
