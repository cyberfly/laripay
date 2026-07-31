<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property DiscountType $discount_type
 * @property int $discount_value
 * @property int $min_order_cents
 * @property int|null $max_uses
 * @property int $times_used
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 */
#[Fillable([
    'code', 'description', 'discount_type', 'discount_value',
    'min_order_cents', 'max_uses', 'starts_at', 'expires_at', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Coupon $coupon): void {
            $coupon->code = strtoupper(trim($coupon->code));
        });
    }

    public function isRedeemable(int $subtotalCents): bool
    {
        $now = Carbon::now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->expires_at === null || $this->expires_at->gte($now))
            && ($this->max_uses === null || $this->times_used < $this->max_uses)
            && $subtotalCents >= $this->min_order_cents;
    }

    /**
     * Atomically claim one redemption, returning whether a use was still
     * available. The `max_uses` guard is part of the UPDATE statement rather
     * than a `lockForUpdate()` read, so two concurrent checkouts can never
     * both claim the last use on any driver, including SQLite where
     * `lockForUpdate()` emits no lock clause.
     */
    public function claimRedemption(): bool
    {
        $claimed = static::query()
            ->whereKey($this->getKey())
            ->where(fn (Builder $query) => $query->whereNull('max_uses')->orWhereColumn('times_used', '<', 'max_uses'))
            ->increment('times_used');

        if ($claimed === 0) {
            return false;
        }

        $this->times_used++;
        $this->syncOriginal();

        return true;
    }

    public function calculateDiscount(int $subtotalCents): int
    {
        $discount = match ($this->discount_type) {
            DiscountType::Fixed => $this->discount_value,
            DiscountType::Percentage => (int) floor($subtotalCents * $this->discount_value / 100),
        };

        return min($discount, $subtotalCents);
    }
}
