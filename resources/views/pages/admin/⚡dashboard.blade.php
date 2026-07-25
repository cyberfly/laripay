<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin dashboard')] class extends Component
{
    public int $productCount = 0;

    public int $pendingOrderCount = 0;

    public int $newOrderCount = 0;

    public function mount(): void
    {
        $this->productCount = Product::query()->count();
        $this->pendingOrderCount = Order::query()->where('payment_status', PaymentStatus::Pending)->count();
        $this->newOrderCount = Order::query()->where('status', OrderStatus::New)->count();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <flux:heading size="xl">{{ __('Admin dashboard') }}</flux:heading>

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Products') }}</flux:subheading>
            <flux:heading size="xl">{{ $productCount }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Pending payments') }}</flux:subheading>
            <flux:heading size="xl">{{ $pendingOrderCount }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:subheading>{{ __('New orders') }}</flux:subheading>
            <flux:heading size="xl">{{ $newOrderCount }}</flux:heading>
        </flux:card>
    </div>

    <div class="flex gap-4">
        <flux:button :href="route('admin.products.index')" wire:navigate>{{ __('Manage products') }}</flux:button>
        <flux:button :href="route('admin.orders.index')" wire:navigate variant="filled">{{ __('Manage orders') }}</flux:button>
    </div>
</div>
