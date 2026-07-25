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
    <flux:heading size="xl">{{ __('New coupon') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.coupons._form')

        <flux:button type="submit" variant="primary">{{ __('Create coupon') }}</flux:button>
    </form>
</div>
