<?php

use App\Enums\DiscountType;
use App\Models\Coupon;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Coupons')] class extends Component
{
    use WithPagination;

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();

        Flux::toast(variant: 'success', text: __('Coupon deleted.'));
    }

    public function with(): array
    {
        return [
            'coupons' => Coupon::query()->latest()->paginate(10),
            'totalCount' => Coupon::query()->count(),
            'activeCount' => Coupon::query()->where('is_active', true)->count(),
            'redemptionCount' => (int) Coupon::query()->sum('times_used'),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Coupons') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create and manage discount codes.') }}</flux:text>
        </div>
        <flux:button :href="route('admin.coupons.create')" wire:navigate variant="primary" icon="plus">
            {{ __('New coupon') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-admin.stat :label="__('Total coupons')" :value="$totalCount" icon="ticket" color="zinc" />
        <x-admin.stat :label="__('Active')" :value="$activeCount" icon="check-circle" color="lime" />
        <x-admin.stat :label="__('Times redeemed')" :value="$redemptionCount" icon="banknotes" color="blue" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Discount') }}</flux:table.column>
            <flux:table.column>{{ __('Used') }}</flux:table.column>
            <flux:table.column>{{ __('Valid period') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($coupons as $coupon)
                <flux:table.row wire:key="coupon-{{ $coupon->id }}">
                    <flux:table.cell variant="strong">{{ $coupon->code }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" :color="$coupon->discount_type->color()">{{ $coupon->discount_type->label() }}</flux:badge>
                            <span>
                                @if ($coupon->discount_type === DiscountType::Percentage)
                                    {{ $coupon->discount_value }}%
                                @else
                                    {{ number_format($coupon->discount_value / 100, 2) }}
                                @endif
                            </span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $coupon->times_used }}{{ $coupon->max_uses ? '/'.$coupon->max_uses : '' }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $coupon->starts_at?->format('Y-m-d') ?? '—' }} &rarr; {{ $coupon->expires_at?->format('Y-m-d') ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$coupon->is_active ? 'lime' : 'zinc'">
                            {{ $coupon->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" :href="route('admin.coupons.edit', $coupon)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $coupon->id }})" wire:confirm="{{ __('Delete this coupon?') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="flex flex-col items-center gap-2 py-10 text-center">
                            <flux:icon name="ticket" class="size-8 text-zinc-400" />
                            <flux:text>{{ __('No coupons yet.') }}</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $coupons->links() }}
</div>
