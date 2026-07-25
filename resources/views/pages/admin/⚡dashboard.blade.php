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

    <div class="flex gap-4">
        <flux:button :href="route('admin.products.index')" wire:navigate>{{ __('Manage products') }}</flux:button>
        <flux:button :href="route('admin.orders.index')" wire:navigate variant="filled">{{ __('Manage orders') }}</flux:button>
    </div>
</div>
