<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::shop')] class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function render()
    {
        $this->order->refresh();

        return $this->view()->title(__('Payment for :order', ['order' => $this->order->order_number]));
    }
};
?>

<div
    class="mx-auto max-w-xl space-y-6 py-10"
    @if ($order->payment_status !== PaymentStatus::Paid) wire:poll.5s @endif
>
    <div class="text-center">
        <flux:heading size="xl">{{ __('Payment instructions') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
            {{ __('Order :number', ['number' => $order->order_number]) }}
        </flux:text>
    </div>

    <flux:card class="space-y-3">
        <div class="flex items-center justify-between">
            <flux:subheading>{{ __('Product') }}</flux:subheading>
            <flux:text class="font-medium text-zinc-900 dark:text-white">{{ $order->product_name_snapshot }}</flux:text>
        </div>

        <div class="flex items-center justify-between">
            <flux:subheading>{{ __('Quantity') }}</flux:subheading>
            <flux:text>{{ $order->quantity }}</flux:text>
        </div>

        <flux:separator variant="subtle" />

        @if ($order->discount_cents_snapshot > 0)
            <div class="flex items-center justify-between">
                <flux:subheading>{{ __('Subtotal') }}</flux:subheading>
                <flux:text>{{ $order->currency_snapshot }} {{ number_format($order->price_cents_snapshot * $order->quantity / 100, 2) }}</flux:text>
            </div>
            <div class="flex items-center justify-between">
                <flux:subheading>{{ __('Discount') }} ({{ $order->coupon_code_snapshot }})</flux:subheading>
                <flux:text class="text-green-600">-{{ $order->currency_snapshot }} {{ number_format($order->discount_cents_snapshot / 100, 2) }}</flux:text>
            </div>
            <flux:separator variant="subtle" />
        @endif

        <div class="flex items-center justify-between">
            <flux:subheading>{{ __('Total') }}</flux:subheading>
            <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }}
            </flux:text>
        </div>
    </flux:card>

    @if ($order->payment_status === PaymentStatus::Paid)
        <flux:callout variant="success" icon="check-circle" :heading="__('Payment confirmed')">
            {{ __('Thank you! Your payment has been confirmed.') }}
        </flux:callout>

        @if ($order->product_type_snapshot === ProductType::Digital->value)
            <flux:button :href="route('shop.downloads.show', [$order, $order->digital_access_token])" variant="primary" class="w-full" icon="arrow-down-tray">
                {{ __('Download your file') }}
            </flux:button>
        @endif
    @else
        <flux:callout variant="warning" icon="clock" :heading="__('Awaiting payment confirmation')">
            {{ __('Please transfer the amount above to our bank account / QR code and reference your order number :number. Our team will confirm your payment shortly.', ['number' => $order->order_number]) }}
        </flux:callout>
    @endif
</div>
