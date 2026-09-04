<?php

use App\Concerns\RequiresAdminAccess;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Orders')] class extends Component
{
    use RequiresAdminAccess, WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $orders = Order::query()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('customer_name', 'like', "%{$this->search}%")
                    ->orWhere('customer_email', 'like', "%{$this->search}%");
            }));

        return [
            'orders' => (clone $orders)->latest()->paginate(15),
            'totalCount' => Order::query()->count(),
            'pendingCount' => Order::query()->where('payment_status', PaymentStatus::Pending)->count(),
            'revenueCents' => Order::query()->where('payment_status', PaymentStatus::Paid)->sum('total_cents_snapshot'),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Orders') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Track and fulfill customer orders.') }}</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-admin.stat :label="__('Total orders')" :value="$totalCount" icon="shopping-bag" color="zinc" />
        <x-admin.stat :label="__('Awaiting payment')" :value="$pendingCount" icon="clock" color="amber" />
        <x-admin.stat :label="__('Revenue collected')" :value="number_format($revenueCents / 100, 2)" icon="banknotes" color="lime" />
    </div>

    <flux:card class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by order #, name or email') }}" icon="magnifying-glass" class="w-full sm:w-72" />

        <flux:select wire:model.live="status" placeholder="{{ __('All statuses') }}" class="w-full sm:w-48">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (OrderStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($search !== '' || $status !== '')
            <flux:button size="sm" variant="ghost" wire:click="$set('search', ''); $set('status', '')">
                {{ __('Clear filters') }}
            </flux:button>
        @endif
    </flux:card>

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
                    <flux:table.cell variant="strong">{{ $order->order_number }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span>{{ $order->customer_name }}</span>
                            <flux:text size="sm">{{ $order->customer_email }}</flux:text>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->quantity }}</flux:table.cell>
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
                    <flux:table.cell>
                        <div class="flex justify-end">
                            <flux:button size="sm" :href="route('admin.orders.show', $order)" wire:navigate>
                                {{ __('View') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <div class="flex flex-col items-center gap-2 py-10 text-center">
                            <flux:icon name="shopping-bag" class="size-8 text-zinc-400" />
                            <flux:text>{{ __('No orders found.') }}</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $orders->links() }}
</div>
