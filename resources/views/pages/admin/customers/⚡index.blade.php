<?php

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function with(): array
    {
        return [
            'customers' => Order::query()
                ->select('customer_email')
                ->selectRaw('MAX(customer_name) as customer_name')
                ->selectRaw('MAX(customer_phone) as customer_phone')
                ->selectRaw('COUNT(*) as orders_count')
                ->selectRaw('SUM(total_cents_snapshot) as total_spent_cents')
                ->selectRaw('MAX(currency_snapshot) as currency_snapshot')
                ->selectRaw('MAX(created_at) as last_order_at')
                ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                    $query->where('customer_name', 'like', "%{$this->search}%")
                        ->orWhere('customer_email', 'like', "%{$this->search}%");
                }))
                ->groupBy('customer_email')
                ->orderByDesc('last_order_at')
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Customers') }}</flux:heading>

            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email') }}" class="w-64" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column>{{ __('Orders') }}</flux:table.column>
                <flux:table.column>{{ __('Total spent') }}</flux:table.column>
                <flux:table.column>{{ __('Last order') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($customers as $customer)
                    <flux:table.row wire:key="customer-{{ $customer->customer_email }}">
                        <flux:table.cell>{{ $customer->customer_name }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->customer_email }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->customer_phone ?: '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->currency_snapshot }} {{ number_format($customer->total_spent_cents / 100, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" :href="route('admin.customers.show', $customer->customer_email)" wire:navigate>
                                {{ __('View') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __('No customers yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $customers->links() }}
    </div>
