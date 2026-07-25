<flux:card class="space-y-6">
    <flux:subheading>{{ __('Details') }}</flux:subheading>

    <flux:input wire:model="form.name" :label="__('Name')" required />

    <x-trix-editor wire-model="form.description" :label="__('Description')" />

    <div class="grid grid-cols-2 gap-4">
        <flux:input wire:model="form.price" :label="__('Price')" type="number" step="0.01" min="0" required />
        <flux:input wire:model="form.currency" :label="__('Currency')" maxlength="3" required />
    </div>

    <flux:select wire:model="form.type" :label="__('Type')">
        <flux:select.option value="physical">{{ __('Physical') }}</flux:select.option>
        <flux:select.option value="digital">{{ __('Digital') }}</flux:select.option>
    </flux:select>

    @if ($form->type === 'digital')
        <flux:input wire:model="form.digitalAccessUrl" :label="__('Digital access URL')" placeholder="https://..." />
    @endif

    <flux:switch wire:model="form.isActive" :label="__('Active')" description="{{ __('Visible in the storefront when enabled.') }}" />
</flux:card>

<flux:card class="space-y-4">
    <flux:subheading>{{ __('Images') }}</flux:subheading>

    @if (! empty($form->existingImages))
        <div class="flex flex-wrap gap-3">
            @foreach ($form->existingImages as $path)
                <div class="group relative" wire:key="existing-{{ $path }}">
                    <img src="{{ Storage::url($path) }}" class="h-24 w-24 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                    <flux:button size="xs" variant="danger" icon="trash" class="absolute top-1 right-1" wire:click="form.removeExistingImage('{{ $path }}')" />
                </div>
            @endforeach
        </div>
    @endif

    <flux:input type="file" wire:model="form.newImages" multiple />
    @error('form.newImages.*') <flux:error>{{ $message }}</flux:error> @enderror
</flux:card>
