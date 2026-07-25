<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin dashboard')] class extends Component
{
    public int $productCount = 0;

    public int $pendingOrderCount = 0;

    public int $newOrderCount = 0;

    /** @var array<int, string> */
    public array $ordersChartLabels = [];

    /** @var array<int, int> */
    public array $ordersChartData = [];

    public function mount(): void
    {
        $this->productCount = Product::query()->count();
        $this->pendingOrderCount = Order::query()->where('payment_status', PaymentStatus::Pending)->count();
        $this->newOrderCount = Order::query()->where('status', OrderStatus::New)->count();

        $days = collect(range(13, 0))->map(fn (int $i) => now()->subDays($i)->format('Y-m-d'));

        $counts = Order::query()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $this->ordersChartLabels = $days->map(fn (string $date) => Carbon::parse($date)->format('d M'))->toArray();
        $this->ordersChartData = $days->map(fn (string $date) => (int) ($counts[$date] ?? 0))->toArray();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div>
        <flux:heading size="xl">{{ __('Admin dashboard') }}</flux:heading>
        <flux:text class="mt-1">{{ __('A quick look at your store performance.') }}</flux:text>
    </div>

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <x-admin.stat :label="__('Products')" :value="$productCount" icon="cube" color="zinc" />
        <x-admin.stat :label="__('Pending payments')" :value="$pendingOrderCount" icon="clock" color="amber" />
        <x-admin.stat :label="__('New orders')" :value="$newOrderCount" icon="shopping-bag" color="blue" />
    </div>

    <flux:card class="space-y-4">
        <flux:subheading>{{ __('Orders (last 14 days)') }}</flux:subheading>

        <div
            wire:ignore
            x-data="{
                labels: @js($ordersChartLabels),
                data: @js($ordersChartData),
                chart: null,
                init() {
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'line',
                        data: {
                            labels: this.labels,
                            datasets: [{
                                label: '{{ __('Orders') }}',
                                data: this.data,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                tension: 0.3,
                                fill: true,
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });
                },
            }"
            class="h-64"
        >
            <canvas x-ref="canvas"></canvas>
        </div>
    </flux:card>

    <div class="flex flex-wrap gap-3">
        <flux:button :href="route('admin.products.index')" wire:navigate icon="cube">{{ __('Manage products') }}</flux:button>
        <flux:button :href="route('admin.orders.index')" wire:navigate icon="shopping-bag">{{ __('Manage orders') }}</flux:button>
        <flux:button :href="route('admin.customers.index')" wire:navigate icon="users">{{ __('View customers') }}</flux:button>
        <flux:button :href="route('admin.coupons.index')" wire:navigate icon="ticket">{{ __('Manage coupons') }}</flux:button>
    </div>
</div>
