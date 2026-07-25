<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order details')] class extends Component
{
    public Order $order;

    public string $status = '';

    public string $trackingNumber = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->status = $order->status->value;
        $this->trackingNumber = (string) $order->tracking_number;
    }

    public function markAsPaid(): void
    {
        $this->order->markAsPaid(Auth::user());

        Flux::toast(variant: 'success', text: __('Order marked as paid.'));
    }

    public function updateFulfillment(): void
    {
        $this->validate([
            'status' => 'required|string',
            'trackingNumber' => 'nullable|string|max:255',
        ]);

        $this->order->update([
            'status' => OrderStatus::from($this->status),
            'tracking_number' => $this->trackingNumber ?: null,
            'shipped_at' => $this->status === OrderStatus::Shipped->value && ! $this->order->shipped_at
                ? now()
                : $this->order->shipped_at,
        ]);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }
};
?>

<div class="max-w-2xl space-y-6">
        <flux:heading size="xl">{{ __('Order') }} {{ $order->order_number }}</flux:heading>

        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Customer') }}</flux:subheading>
            <flux:text>{{ $order->customer_name }} &mdash; {{ $order->customer_email }}</flux:text>
            @if ($order->customer_phone)
                <flux:text>{{ $order->customer_phone }}</flux:text>
            @endif

            @if ($order->shipping_address_line1)
                <flux:text>
                    {{ $order->shipping_address_line1 }} {{ $order->shipping_address_line2 }}<br>
                    {{ $order->city }}, {{ $order->state }} {{ $order->postcode }}<br>
                    {{ $order->country }}
                </flux:text>
            @endif
        </flux:card>

        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Product') }}</flux:subheading>
            <flux:text>{{ $order->product_name_snapshot }} ({{ $order->product_type_snapshot }})</flux:text>
            <flux:text>{{ $order->currency_snapshot }} {{ number_format($order->price_cents_snapshot / 100, 2) }}</flux:text>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:subheading>{{ __('Payment') }}</flux:subheading>
            <flux:badge :color="$order->payment_status === PaymentStatus::Paid ? 'lime' : 'zinc'">
                {{ $order->payment_status->value }}
            </flux:badge>

            @if ($order->payment_status !== PaymentStatus::Paid)
                <flux:button variant="primary" wire:click="markAsPaid" wire:confirm="{{ __('Mark this order as paid?') }}">
                    {{ __('Mark as paid') }}
                </flux:button>
            @endif
        </flux:card>

        <flux:card class="space-y-4">
            <flux:subheading>{{ __('Fulfillment') }}</flux:subheading>

            <flux:select wire:model="status" :label="__('Status')">
                @foreach (OrderStatus::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($order->product_type_snapshot === 'physical')
                <flux:input wire:model="trackingNumber" :label="__('Tracking number')" />
            @endif

            <flux:button wire:click="updateFulfillment" variant="primary">{{ __('Update') }}</flux:button>
        </flux:card>
    </div>
