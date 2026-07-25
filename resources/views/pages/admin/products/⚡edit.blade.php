<?php

use App\Livewire\Forms\ProductForm;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit product')] class extends Component
{
    use WithFileUploads;

    public Product $product;

    public ProductForm $form;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->form->setProduct($product);
    }

    public function save(): void
    {
        $this->form->update();

        Flux::toast(variant: 'success', text: __('Product updated.'));
    }
};
?>

<div class="max-w-2xl space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>{{ __('Products') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Edit product') }}</flux:heading>
        <flux:badge :color="$product->is_active ? 'lime' : 'zinc'">
            {{ $product->is_active ? __('Active') : __('Inactive') }}
        </flux:badge>
    </div>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.products._form')

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.products.index')" wire:navigate>{{ __('Back to products') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</div>
