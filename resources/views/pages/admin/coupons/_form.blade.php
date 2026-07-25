<flux:input wire:model="form.code" :label="__('Code')" required />

<flux:input wire:model="form.description" :label="__('Description')" />

<flux:select wire:model="form.discountType" :label="__('Discount type')">
    <flux:select.option value="fixed">{{ __('Fixed amount') }}</flux:select.option>
    <flux:select.option value="percentage">{{ __('Percentage') }}</flux:select.option>
</flux:select>

@if ($form->discountType === 'percentage')
    <flux:input wire:model="form.discountValue" :label="__('Discount percentage')" type="number" step="1" min="0" max="100" required />
@else
    <flux:input wire:model="form.discountValue" :label="__('Discount amount')" type="number" step="0.01" min="0" required />
@endif

<flux:input wire:model="form.minOrder" :label="__('Minimum order amount')" type="number" step="0.01" min="0" />

<flux:input wire:model="form.maxUses" :label="__('Maximum uses')" type="number" step="1" min="1" placeholder="{{ __('Unlimited') }}" />

<div class="grid grid-cols-2 gap-4">
    <flux:input wire:model="form.startsAt" :label="__('Starts at')" type="date" />
    <flux:input wire:model="form.expiresAt" :label="__('Expires at')" type="date" />
</div>

<flux:switch wire:model="form.isActive" :label="__('Active')" />
