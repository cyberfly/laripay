<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $order_number
 * @property int $product_id
 * @property int|null $coupon_id
 * @property string $product_name_snapshot
 * @property int $price_cents_snapshot
 * @property string $currency_snapshot
 * @property string $product_type_snapshot
 * @property int $quantity
 * @property string|null $coupon_code_snapshot
 * @property int $discount_cents_snapshot
 * @property int $total_cents_snapshot
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_phone
 * @property string|null $shipping_address_line1
 * @property string|null $shipping_address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postcode
 * @property string|null $country
 * @property string $payment_method
 * @property PaymentStatus $payment_status
 * @property string|null $payment_reference
 * @property string|null $xendit_invoice_id
 * @property Carbon|null $xendit_expires_at
 * @property Carbon|null $paid_at
 * @property int|null $payment_confirmed_by
 * @property OrderStatus $status
 * @property string|null $tracking_number
 * @property Carbon|null $shipped_at
 * @property string|null $digital_access_token
 */
#[Fillable([
    'product_id', 'coupon_id', 'product_name_snapshot', 'price_cents_snapshot', 'currency_snapshot', 'product_type_snapshot', 'quantity',
    'coupon_code_snapshot', 'discount_cents_snapshot', 'total_cents_snapshot',
    'customer_name', 'customer_email', 'customer_phone',
    'shipping_address_line1', 'shipping_address_line2', 'city', 'state', 'postcode', 'country',
    'payment_method', 'payment_status', 'payment_reference', 'xendit_invoice_id', 'xendit_expires_at',
    'status', 'tracking_number', 'shipped_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'xendit_expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->order_number ??= 'ORD-'.strtoupper(Str::random(10));
            $order->digital_access_token ??= Str::random(40);
        });

        static::created(function (Order $order): void {
            event(new OrderCreated($order));
        });
    }

    public function markAsPaid(?User $confirmedBy = null): void
    {
        $this->forceFill([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => Carbon::now(),
            'payment_confirmed_by' => $confirmedBy?->id,
        ])->save();

        event(new OrderPaid($this));
    }
}
