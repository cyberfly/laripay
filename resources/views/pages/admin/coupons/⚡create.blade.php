<?php

use App\Livewire\Forms\CouponForm;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New coupon')] class extends Component
{
    public CouponForm $form;

    public function save(): void
    {
        $coupon = $this->form->store();

        Flux::toast(variant: 'success', text: __('Coupon created.'));

        $this->redirect(route('admin.coupons.edit', $coupon), navigate: true);
    }
};
?>

<div class="max-w-2xl space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.coupons.index')" wire:navigate>{{ __('Coupons') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('New coupon') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">{{ __('New coupon') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.coupons._form')

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.coupons.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Create coupon') }}</flux:button>
        </div>
    </form>
</div>
