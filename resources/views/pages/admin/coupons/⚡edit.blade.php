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
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.coupons.index')" wire:navigate>{{ __('Coupons') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $coupon->code }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Edit coupon') }}</flux:heading>
        <flux:badge :color="$coupon->is_active ? 'lime' : 'zinc'">
            {{ $coupon->is_active ? __('Active') : __('Inactive') }}
        </flux:badge>
    </div>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.coupons._form')

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.coupons.index')" wire:navigate>{{ __('Back to coupons') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</div>
