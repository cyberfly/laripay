<?php

use App\Livewire\Forms\ProductForm;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('New product')] class extends Component
{
    use WithFileUploads;

    public ProductForm $form;

    public function save(): void
    {
        $product = $this->form->store();

        Flux::toast(variant: 'success', text: __('Product created.'));

        $this->redirect(route('admin.products.edit', $product), navigate: true);
    }
};
?>

<div class="max-w-2xl space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>{{ __('Products') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('New product') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">{{ __('New product') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.products._form')

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.products.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Create product') }}</flux:button>
        </div>
    </form>
</div>
