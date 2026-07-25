<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Orders')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Orders') }}</flux:heading>

            <flux:select wire:model.live="status" placeholder="{{ __('All statuses') }}" class="w-48">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach (OrderStatus::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Order #') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Qty') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Payment') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($orders as $order)
                    <flux:table.row wire:key="order-{{ $order->id }}">
                        <flux:table.cell>{{ $order->order_number }}</flux:table.cell>
                        <flux:table.cell>{{ $order->customer_name }}</flux:table.cell>
                        <flux:table.cell>{{ $order->quantity }}</flux:table.cell>
                        <flux:table.cell>{{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$order->payment_status === PaymentStatus::Paid ? 'lime' : 'zinc'">
                                {{ $order->payment_status->value }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $order->status->value }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" :href="route('admin.orders.show', $order)" wire:navigate>
                                {{ __('View') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __('No orders yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $orders->links() }}
    </div>
