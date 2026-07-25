<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('product_name_snapshot');
            $table->unsignedInteger('price_cents_snapshot');
            $table->string('currency_snapshot', 3);
            $table->string('product_type_snapshot');

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            $table->string('shipping_address_line1')->nullable();
            $table->string('shipping_address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();

            $table->string('payment_method')->default('manual');
            $table->string('payment_status')->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('new');
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();

            $table->string('digital_access_token')->unique()->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
