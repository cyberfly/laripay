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
    <flux:heading size="xl">{{ __('New product') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.admin.products._form')

        <flux:button type="submit" variant="primary">{{ __('Create product') }}</flux:button>
    </form>
</div>
