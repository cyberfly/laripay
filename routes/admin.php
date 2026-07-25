<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('products', 'pages::admin.products.index')->name('products.index');
    Route::livewire('products/create', 'pages::admin.products.create')->name('products.create');
    Route::livewire('products/{product}/edit', 'pages::admin.products.edit')->name('products.edit');

    Route::livewire('orders', 'pages::admin.orders.index')->name('orders.index');
    Route::livewire('orders/{order}', 'pages::admin.orders.show')->name('orders.show');

    Route::livewire('coupons', 'pages::admin.coupons.index')->name('coupons.index');
    Route::livewire('coupons/create', 'pages::admin.coupons.create')->name('coupons.create');
    Route::livewire('coupons/{coupon}/edit', 'pages::admin.coupons.edit')->name('coupons.edit');

    Route::livewire('customers', 'pages::admin.customers.index')->name('customers.index');
    Route::livewire('customers/{email}', 'pages::admin.customers.show')->name('customers.show');
});
