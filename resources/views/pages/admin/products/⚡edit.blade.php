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
    <flux:heading size="xl">{{ __('Edit product') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.products._form')

        <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
    </form>
</div>
