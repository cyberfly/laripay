<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

new #[Title('Customer details')] class extends Component
{
    public string $email;

    public function mount(string $email): void
    {
        if (! Order::query()->where('customer_email', $email)->exists()) {
            throw new NotFoundHttpException;
        }

        $this->email = $email;
    }

    public function with(): array
    {
        $orders = Order::query()
            ->where('customer_email', $this->email)
            ->latest()
            ->get();

        return [
            'orders' => $orders,
            'customer_name' => $orders->first()?->customer_name,
            'customer_phone' => $orders->firstWhere('customer_phone', '!=', null)?->customer_phone,
            'total_spent' => $orders->sum('total_cents_snapshot'),
            'currency' => $orders->first()?->currency_snapshot,
        ];
    }
};
?>

<div class="max-w-3xl space-y-6">
        <flux:heading size="xl">{{ $customer_name }}</flux:heading>

        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Contact') }}</flux:subheading>
            <flux:text>{{ $email }}</flux:text>
            @if ($customer_phone)
                <flux:text>{{ $customer_phone }}</flux:text>
            @endif
        </flux:card>

        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Summary') }}</flux:subheading>
            <flux:text>{{ __('Orders') }}: {{ $orders->count() }}</flux:text>
            <flux:text>{{ __('Total spent') }}: {{ $currency }} {{ number_format($total_spent / 100, 2) }}</flux:text>
        </flux:card>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Order #') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Payment') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($orders as $order)
                    <flux:table.row wire:key="order-{{ $order->id }}">
                        <flux:table.cell>{{ $order->order_number }}</flux:table.cell>
                        <flux:table.cell>{{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$order->payment_status === PaymentStatus::Paid ? 'lime' : 'zinc'">
                                {{ $order->payment_status->value }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $order->status->value }}</flux:table.cell>
                        <flux:table.cell>{{ $order->created_at->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" :href="route('admin.orders.show', $order)" wire:navigate>
                                {{ __('View') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
