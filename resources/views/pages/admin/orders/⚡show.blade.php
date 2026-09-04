<?php

use App\Concerns\RequiresAdminAccess;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order details')] class extends Component
{
    use RequiresAdminAccess;

    #[Locked]
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

<div class="max-w-4xl space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.orders.index')" wire:navigate>{{ __('Orders') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $order->order_number }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ __('Order') }} {{ $order->order_number }}</flux:heading>
            <flux:text class="mt-1">{{ __('Placed on') }} {{ $order->created_at->format('Y-m-d H:i') }}</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</flux:badge>
            <flux:badge :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Customer') }}</flux:subheading>
            <flux:text>
                <flux:link :href="route('admin.customers.show', $order->customer_email)" wire:navigate>
                    {{ $order->customer_name }}
                </flux:link>
                &mdash; {{ $order->customer_email }}
            </flux:text>
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
            <flux:text>{{ __('Quantity') }}: {{ $order->quantity }}</flux:text>

            @if ($order->discount_cents_snapshot > 0)
                <flux:text>{{ __('Subtotal') }}: {{ $order->currency_snapshot }} {{ number_format($order->price_cents_snapshot * $order->quantity / 100, 2) }}</flux:text>
                <flux:text class="text-green-600">{{ __('Discount') }} ({{ $order->coupon_code_snapshot }}): -{{ $order->currency_snapshot }} {{ number_format($order->discount_cents_snapshot / 100, 2) }}</flux:text>
                <flux:text class="font-semibold">{{ __('Total') }}: {{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }}</flux:text>
            @else
                <flux:text class="font-semibold">{{ __('Total') }}: {{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }}</flux:text>
            @endif
        </flux:card>

        <flux:card class="space-y-4">
            <flux:subheading>{{ __('Payment') }}</flux:subheading>
            <flux:badge :color="$order->payment_status->color()">
                {{ $order->payment_status->label() }}
            </flux:badge>

            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Method') }}: {{ ucfirst($order->payment_method) }}
            </flux:text>

            @if ($order->payment_reference)
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Reference') }}: {{ $order->payment_reference }}
                </flux:text>
            @endif

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
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($order->product_type_snapshot === 'physical')
                <flux:input wire:model="trackingNumber" :label="__('Tracking number')" />
            @endif

            <flux:button wire:click="updateFulfillment" variant="primary">{{ __('Update') }}</flux:button>
        </flux:card>
    </div>
</div>
