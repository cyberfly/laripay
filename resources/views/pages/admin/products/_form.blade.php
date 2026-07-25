<flux:input wire:model="form.name" :label="__('Name')" required />

<flux:textarea wire:model="form.description" :label="__('Description')" rows="4" />

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

<flux:switch wire:model="form.isActive" :label="__('Active')" />

<div class="space-y-2">
    <flux:label>{{ __('Images') }}</flux:label>

    @if (! empty($form->existingImages))
        <div class="flex flex-wrap gap-3">
            @foreach ($form->existingImages as $path)
                <div class="relative" wire:key="existing-{{ $path }}">
                    <img src="{{ Storage::url($path) }}" class="h-20 w-20 rounded object-cover">
                    <flux:button size="xs" variant="danger" class="mt-1 w-full" wire:click="form.removeExistingImage('{{ $path }}')">
                        {{ __('Remove') }}
                    </flux:button>
                </div>
            @endforeach
        </div>
    @endif

    <flux:input type="file" wire:model="form.newImages" multiple />
    @error('form.newImages.*') <flux:error>{{ $message }}</flux:error> @enderror
</div>
