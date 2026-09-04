<?php

use App\Concerns\RequiresAdminAccess;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

new #[Title('Customer details')] class extends Component
{
    use RequiresAdminAccess;

    #[Locked]
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
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>{{ __('Customers') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $customer_name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center gap-4">
        <flux:avatar size="lg" :name="$customer_name" color="auto" />
        <div>
            <flux:heading size="xl">{{ $customer_name }}</flux:heading>
            <flux:text>{{ $email }}</flux:text>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-admin.stat :label="__('Orders')" :value="$orders->count()" icon="shopping-bag" color="zinc" />
        <x-admin.stat :label="__('Total spent')" :value="$currency.' '.number_format($total_spent / 100, 2)" icon="banknotes" color="lime" />
        <x-admin.stat :label="__('Avg. order value')" :value="$currency.' '.number_format($orders->count() ? $total_spent / $orders->count() / 100 : 0, 2)" icon="chart-bar" color="blue" />
    </div>

    @if ($customer_phone)
        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Contact') }}</flux:subheading>
            <flux:text>{{ $email }}</flux:text>
            <flux:text>{{ $customer_phone }}</flux:text>
        </flux:card>
    @endif

    <div class="space-y-3">
        <flux:subheading>{{ __('Order history') }}</flux:subheading>

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
                        <flux:table.cell variant="strong">{{ $order->order_number }}</flux:table.cell>
                        <flux:table.cell>{{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$order->payment_status->color()">
                                {{ $order->payment_status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$order->status->color()">
                                {{ $order->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $order->created_at->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button size="sm" :href="route('admin.orders.show', $order)" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
