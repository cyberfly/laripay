<?php

namespace App\Livewire\Forms;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CouponForm extends Form
{
    public ?Coupon $coupon = null;

    public string $code = '';

    public string $description = '';

    public string $discountType = 'fixed';

    public string $discountValue = '';

    public string $minOrder = '0';

    public string $maxUses = '';

    public string $startsAt = '';

    public string $expiresAt = '';

    public bool $isActive = true;

    public function setCoupon(Coupon $coupon): void
    {
        $this->coupon = $coupon;
        $this->code = $coupon->code;
        $this->description = (string) $coupon->description;
        $this->discountType = $coupon->discount_type->value;
        $this->discountValue = $coupon->discount_type === DiscountType::Percentage
            ? (string) $coupon->discount_value
            : number_format($coupon->discount_value / 100, 2, '.', '');
        $this->minOrder = number_format($coupon->min_order_cents / 100, 2, '.', '');
        $this->maxUses = $coupon->max_uses !== null ? (string) $coupon->max_uses : '';
        $this->startsAt = $coupon->starts_at?->format('Y-m-d') ?? '';
        $this->expiresAt = $coupon->expires_at?->format('Y-m-d') ?? '';
        $this->isActive = $coupon->is_active;
    }

    public function store(): Coupon
    {
        $this->validate($this->rules());

        return Coupon::create($this->attributes());
    }

    public function update(): Coupon
    {
        $this->validate($this->rules());

        $this->coupon->update($this->attributes());

        return $this->coupon;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($this->coupon?->id)],
            'description' => 'nullable|string|max:255',
            'discountType' => 'required|in:fixed,percentage',
            'discountValue' => $this->discountType === 'percentage'
                ? 'required|numeric|min:0|max:100'
                : 'required|numeric|min:0',
            'minOrder' => 'nullable|numeric|min:0',
            'maxUses' => 'nullable|integer|min:1',
            'startsAt' => 'nullable|date',
            'expiresAt' => 'nullable|date|after_or_equal:startsAt',
            'isActive' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(): array
    {
        return [
            'code' => strtoupper(trim($this->code)),
            'description' => $this->description ?: null,
            'discount_type' => DiscountType::from($this->discountType),
            'discount_value' => $this->discountType === 'percentage'
                ? (int) $this->discountValue
                : (int) round(((float) $this->discountValue) * 100),
            'min_order_cents' => (int) round(((float) ($this->minOrder ?: 0)) * 100),
            'max_uses' => $this->maxUses !== '' ? (int) $this->maxUses : null,
            'starts_at' => $this->startsAt ?: null,
            'expires_at' => $this->expiresAt ?: null,
            'is_active' => $this->isActive,
        ];
    }
}
