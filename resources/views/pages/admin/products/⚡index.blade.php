<?php

use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component
{
    use WithPagination;

    public function delete(Product $product): void
    {
        $product->delete();

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }

    public function with(): array
    {
        return [
            'products' => Product::query()->latest()->paginate(10),
        ];
    }
};
?>

<div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Products') }}</flux:heading>
            <flux:button :href="route('admin.products.create')" wire:navigate variant="primary">
                {{ __('New product') }}
            </flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Price') }}</flux:table.column>
                <flux:table.column>{{ __('Active') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($products as $product)
                    <flux:table.row wire:key="product-{{ $product->id }}">
                        <flux:table.cell>{{ $product->name }}</flux:table.cell>
                        <flux:table.cell>{{ $product->type->value }}</flux:table.cell>
                        <flux:table.cell>{{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $product->is_active ? __('Yes') : __('No') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" :href="route('admin.products.edit', $product)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $product->id }})" wire:confirm="{{ __('Delete this product?') }}">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">{{ __('No products yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $products->links() }}
    </div>
