<?php

use App\Enums\ProductType;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::shop')] #[Title('Products')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->where('is_active', true)
                ->latest()
                ->paginate(12),
        ];
    }
};
?>

<div>
    <section class="relative overflow-hidden rounded-2xl bg-zinc-900 px-6 py-16 text-center dark:bg-zinc-950 sm:py-20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,var(--color-zinc-700),transparent_60%)] opacity-40"></div>

        <div class="relative mx-auto max-w-2xl space-y-4">
            <flux:badge color="zinc" variant="pill" class="bg-white/10 text-white ring-white/20">
                {{ __('Welcome to :app', ['app' => config('app.name')]) }}
            </flux:badge>

            <flux:heading size="xl" level="1" class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('Shop our products') }}
            </flux:heading>

            <flux:text size="lg" class="text-zinc-300">
                {{ __('Browse our catalog of physical and digital products, and check out securely in minutes.') }}
            </flux:text>
        </div>
    </section>

    <section class="py-10 sm:py-12">
        <div class="mb-6 flex items-center justify-between">
            <flux:heading size="lg">{{ __('All products') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}
            </flux:text>
        </div>

        @if ($products->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-300 py-20 text-center dark:border-zinc-700">
                <flux:icon.shopping-bag class="size-10 text-zinc-400" />
                <flux:heading size="lg">{{ __('No products available yet') }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Please check back soon.') }}</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <a
                        wire:key="product-{{ $product->id }}"
                        href="{{ route('shop.products.show', $product) }}"
                        wire:navigate
                        class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:-translate-y-1 hover:shadow-lg hover:shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:shadow-black/20"
                    >
                        <div class="relative aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            @if (! empty($product->images))
                                <img
                                    src="{{ Storage::url($product->images[0]) }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <flux:icon.photo class="size-10 text-zinc-300 dark:text-zinc-700" />
                                </div>
                            @endif

                            <flux:badge
                                size="sm"
                                :color="$product->type === ProductType::Digital ? 'blue' : 'zinc'"
                                class="absolute top-3 left-3 shadow-sm"
                            >
                                {{ $product->type === ProductType::Digital ? __('Digital') : __('Physical') }}
                            </flux:badge>
                        </div>

                        <div class="flex flex-1 flex-col gap-1 p-4">
                            <flux:heading class="line-clamp-1">{{ $product->name }}</flux:heading>

                            @if ($product->description)
                                <flux:text size="sm" class="line-clamp-2 text-zinc-500 dark:text-zinc-400">
                                    {{ $product->description }}
                                </flux:text>
                            @endif

                            <div class="mt-3 flex items-center justify-between">
                                <flux:text class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }}
                                </flux:text>
                                <flux:icon.arrow-right class="size-4 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-zinc-900 dark:group-hover:text-white" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @endif
    </section>
</div>
