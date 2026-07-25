<?php

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
        ];
    }
};
?>

<div class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Coupons') }}</flux:heading>
        <flux:button :href="route('admin.coupons.create')" wire:navigate variant="primary">
            {{ __('New coupon') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Discount') }}</flux:table.column>
            <flux:table.column>{{ __('Used') }}</flux:table.column>
            <flux:table.column>{{ __('Valid period') }}</flux:table.column>
            <flux:table.column>{{ __('Active') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($coupons as $coupon)
                <flux:table.row wire:key="coupon-{{ $coupon->id }}">
                    <flux:table.cell>{{ $coupon->code }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($coupon->discount_type === \App\Enums\DiscountType::Percentage)
                            {{ $coupon->discount_value }}%
                        @else
                            {{ number_format($coupon->discount_value / 100, 2) }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $coupon->times_used }}{{ $coupon->max_uses ? '/'.$coupon->max_uses : '' }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $coupon->starts_at?->format('Y-m-d') ?? '—' }} &rarr; {{ $coupon->expires_at?->format('Y-m-d') ?? '—' }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $coupon->is_active ? __('Yes') : __('No') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" :href="route('admin.coupons.edit', $coupon)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $coupon->id }})" wire:confirm="{{ __('Delete this coupon?') }}">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __('No coupons yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $coupons->links() }}
</div>
