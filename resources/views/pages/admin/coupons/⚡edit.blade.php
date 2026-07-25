<?php

use App\Livewire\Forms\CouponForm;
use App\Models\Coupon;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit coupon')] class extends Component
{
    public Coupon $coupon;

    public CouponForm $form;

    public function mount(Coupon $coupon): void
    {
        $this->coupon = $coupon;
        $this->form->setCoupon($coupon);
    }

    public function save(): void
    {
        $this->form->update();

        Flux::toast(variant: 'success', text: __('Coupon updated.'));
    }
};
?>

<div class="max-w-2xl space-y-6">
    <flux:heading size="xl">{{ __('Edit coupon') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.coupons._form')

        <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
    </form>
</div>
