<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('product_id')
                ->constrained()->nullOnDelete();
            $table->string('coupon_code_snapshot')->nullable()->after('product_type_snapshot');
            $table->unsignedInteger('discount_cents_snapshot')->default(0)->after('coupon_code_snapshot');
            $table->unsignedInteger('total_cents_snapshot')->default(0)->after('discount_cents_snapshot');
        });

        DB::table('orders')->update(['total_cents_snapshot' => DB::raw('price_cents_snapshot')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code_snapshot', 'discount_cents_snapshot', 'total_cents_snapshot']);
        });
    }
};
