<?php

use App\Concerns\RequiresAdminAccess;
use App\Enums\ProductType;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component
{
    use RequiresAdminAccess, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $active = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingActive(): void
    {
        $this->resetPage();
    }

    public function delete(Product $product): void
    {
        $product->delete();

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
                ->when($this->active !== '', fn ($query) => $query->where('is_active', $this->active === 'active'))
                ->latest()
                ->paginate(10),
            'totalCount' => Product::query()->count(),
            'activeCount' => Product::query()->where('is_active', true)->count(),
            'inactiveCount' => Product::query()->where('is_active', false)->count(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Products') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Manage your catalog of physical and digital products.') }}</flux:text>
        </div>
        <flux:button :href="route('admin.products.create')" wire:navigate variant="primary" icon="plus">
            {{ __('New product') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-admin.stat :label="__('Total products')" :value="$totalCount" icon="cube" color="zinc" />
        <x-admin.stat :label="__('Active')" :value="$activeCount" icon="check-circle" color="lime" />
        <x-admin.stat :label="__('Inactive')" :value="$inactiveCount" icon="pause-circle" color="amber" />
    </div>

    <flux:card class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name') }}" icon="magnifying-glass" class="w-full sm:w-64" />

        <flux:select wire:model.live="type" placeholder="{{ __('All types') }}" class="w-full sm:w-40">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            @foreach (ProductType::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="active" placeholder="{{ __('All statuses') }}" class="w-full sm:w-40">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
        </flux:select>

        @if ($search !== '' || $type !== '' || $active !== '')
            <flux:button size="sm" variant="ghost" wire:click="$set('search', ''); $set('type', ''); $set('active', '')">
                {{ __('Clear filters') }}
            </flux:button>
        @endif
    </flux:card>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Price') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($products as $product)
                <flux:table.row wire:key="product-{{ $product->id }}">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            @if (! empty($product->images[0] ?? null))
                                <img src="{{ Storage::url($product->images[0]) }}" class="size-10 shrink-0 rounded-lg object-cover" alt="">
                            @else
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                    <flux:icon name="photo" class="size-5 text-zinc-400" />
                                </div>
                            @endif
                            <span class="font-medium">{{ $product->name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$product->type->color()">{{ $product->type->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">{{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$product->is_active ? 'lime' : 'zinc'">
                            {{ $product->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" :href="route('admin.products.edit', $product)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $product->id }})" wire:confirm="{{ __('Delete this product?') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center gap-2 py-10 text-center">
                            <flux:icon name="cube" class="size-8 text-zinc-400" />
                            <flux:text>{{ __('No products found.') }}</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $products->links() }}
</div>
