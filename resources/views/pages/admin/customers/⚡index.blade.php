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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

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
            'totalCustomers' => Order::query()->distinct('customer_email')->count('customer_email'),
            'totalRevenueCents' => Order::query()->sum('total_cents_snapshot'),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Customers') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Everyone who has placed an order.') }}</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-admin.stat :label="__('Total customers')" :value="$totalCustomers" icon="users" color="zinc" />
        <x-admin.stat :label="__('Lifetime revenue')" :value="number_format($totalRevenueCents / 100, 2)" icon="banknotes" color="lime" />
    </div>

    <flux:card>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email') }}" icon="magnifying-glass" class="w-full sm:w-72" />
    </flux:card>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Customer') }}</flux:table.column>
            <flux:table.column>{{ __('Phone') }}</flux:table.column>
            <flux:table.column>{{ __('Orders') }}</flux:table.column>
            <flux:table.column>{{ __('Total spent') }}</flux:table.column>
            <flux:table.column>{{ __('Last order') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($customers as $customer)
                <flux:table.row wire:key="customer-{{ $customer->customer_email }}">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :name="$customer->customer_name" color="auto" />
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $customer->customer_name }}</span>
                                <flux:text size="sm">{{ $customer->customer_email }}</flux:text>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $customer->customer_phone ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $customer->currency_snapshot }} {{ number_format($customer->total_spent_cents / 100, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end">
                            <flux:button size="sm" :href="route('admin.customers.show', $customer->customer_email)" wire:navigate>
                                {{ __('View') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="flex flex-col items-center gap-2 py-10 text-center">
                            <flux:icon name="users" class="size-8 text-zinc-400" />
                            <flux:text>{{ __('No customers found.') }}</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $customers->links() }}
</div>
