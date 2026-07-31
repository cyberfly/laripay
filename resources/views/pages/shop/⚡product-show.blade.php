<?php

use App\Enums\ProductType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::shop')] class extends Component
{
    public Product $product;

    public int $quantity = 1;

    public string $customerName = '';

    public string $customerEmail = '';

    public string $customerPhone = '';

    public string $shippingAddressLine1 = '';

    public string $shippingAddressLine2 = '';

    public string $city = '';

    public string $state = '';

    public string $postcode = '';

    public string $country = '';

    public string $couponCode = '';

    public ?Coupon $appliedCoupon = null;

    public ?string $couponError = null;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product;
    }

    public function render()
    {
        return $this->view()->title($this->product->name);
    }

    public function applyCoupon(): void
    {
        $this->couponError = null;
        $code = strtoupper(trim($this->couponCode));

        if ($code === '') {
            $this->appliedCoupon = null;

            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isRedeemable($this->subtotalCents)) {
            $this->appliedCoupon = null;
            $this->couponError = __('This coupon code is invalid or cannot be applied to this order.');

            return;
        }

        $this->appliedCoupon = $coupon;
    }

    public function removeCoupon(): void
    {
        $this->couponCode = '';
        $this->appliedCoupon = null;
        $this->couponError = null;
    }

    #[Computed]
    public function subtotalCents(): int
    {
        return $this->product->price_cents * $this->quantity;
    }

    #[Computed]
    public function discountCents(): int
    {
        return $this->appliedCoupon?->calculateDiscount($this->subtotalCents) ?? 0;
    }

    #[Computed]
    public function totalCents(): int
    {
        return max(0, $this->subtotalCents - $this->discountCents);
    }

    public function submitOrder(): void
    {
        $rules = [
            'quantity' => 'required|integer|min:1|max:100',
            'customerName' => 'required|string|max:255',
            'customerEmail' => 'required|email|max:255',
            'customerPhone' => 'nullable|string|max:255',
        ];

        if ($this->product->type === ProductType::Physical) {
            $rules = [...$rules, ...[
                'shippingAddressLine1' => 'required|string|max:255',
                'shippingAddressLine2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postcode' => 'required|string|max:20',
                'country' => 'required|string|max:255',
            ]];
        }

        $validated = $this->validate($rules);

        $order = DB::transaction(function () use ($validated) {
            $quantity = $validated['quantity'];
            $subtotalCents = $this->product->price_cents * $quantity;
            $coupon = null;
            $discountCents = 0;

            if ($this->appliedCoupon) {
                $coupon = Coupon::whereKey($this->appliedCoupon->id)->first();

                if (! $coupon || ! $coupon->isRedeemable($subtotalCents) || ! $coupon->claimRedemption()) {
                    throw ValidationException::withMessages([
                        'couponCode' => __('This coupon is no longer valid.'),
                    ]);
                }

                $discountCents = $coupon->calculateDiscount($subtotalCents);
            }

            $order = Order::create([
                'product_id' => $this->product->id,
                'coupon_id' => $coupon?->id,
                'product_name_snapshot' => $this->product->name,
                'price_cents_snapshot' => $this->product->price_cents,
                'currency_snapshot' => $this->product->currency,
                'product_type_snapshot' => $this->product->type->value,
                'quantity' => $quantity,
                'coupon_code_snapshot' => $coupon?->code,
                'discount_cents_snapshot' => $discountCents,
                'total_cents_snapshot' => $subtotalCents - $discountCents,
                'customer_name' => $validated['customerName'],
                'customer_email' => $validated['customerEmail'],
                'customer_phone' => $validated['customerPhone'] ?? null,
                'shipping_address_line1' => $validated['shippingAddressLine1'] ?? null,
                'shipping_address_line2' => $validated['shippingAddressLine2'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postcode' => $validated['postcode'] ?? null,
                'country' => $validated['country'] ?? null,
                'payment_method' => config('payments.default'),
                'payment_status' => 'pending',
                'status' => 'new',
            ]);

            return $order;
        });

        $gateways = config('payments.gateways');
        $gatewayClass = $gateways[$order->payment_method] ?? $gateways['manual'];

        /** @var PaymentGateway $gateway */
        $gateway = app($gatewayClass);

        $result = $gateway->initiate($order);

        $this->redirect($result->redirectUrl, navigate: false);
    }
};
?>

<div class="py-8">
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item :href="route('shop.index')" wire:navigate>{{ __('Products') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
        <div class="space-y-6">
            <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                @if (! empty($product->images))
                    <img
                        src="{{ Storage::url($product->images[0]) }}"
                        alt="{{ $product->name }}"
                        class="h-full w-full object-cover"
                    >
                @else
                    <div class="flex h-full w-full items-center justify-center">
                        <flux:icon.photo class="size-16 text-zinc-300 dark:text-zinc-700" />
                    </div>
                @endif
            </div>

            @if (count($product->images ?? []) > 1)
                <div class="grid grid-cols-5 gap-3">
                    @foreach (array_slice($product->images, 0, 5) as $image)
                        <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <img src="{{ Storage::url($image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="space-y-3">
                <flux:badge size="sm" :color="$product->type === ProductType::Digital ? 'blue' : 'zinc'">
                    {{ $product->type === ProductType::Digital ? __('Digital product') : __('Physical product') }}
                </flux:badge>

                <flux:heading size="xl" level="1">{{ $product->name }}</flux:heading>

                <flux:text size="xl" class="font-semibold text-zinc-900 dark:text-white">
                    {{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }}
                </flux:text>
            </div>

            @if ($product->description)
                <flux:text class="rich-text leading-relaxed text-zinc-600 dark:text-zinc-300">
                    {!! $product->description !!}
                </flux:text>
            @endif

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                    <flux:icon.shield-check class="size-5 shrink-0 text-zinc-400" />
                    <flux:text size="sm">{{ __('Secure checkout') }}</flux:text>
                </div>

                <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                    @if ($product->type === ProductType::Digital)
                        <flux:icon.bolt class="size-5 shrink-0 text-zinc-400" />
                        <flux:text size="sm">{{ __('Instant delivery') }}</flux:text>
                    @else
                        <flux:icon.truck class="size-5 shrink-0 text-zinc-400" />
                        <flux:text size="sm">{{ __('Shipped to your address') }}</flux:text>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:sticky lg:top-8 lg:self-start">
            <flux:card class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ __('Order this product') }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        {{ __('Fill in your details below to place your order.') }}
                    </flux:text>
                </div>

                <form wire:submit="submitOrder" class="space-y-4">
                    <flux:input wire:model.live="quantity" type="number" min="1" max="100" :label="__('Quantity')" required />

                    <flux:input wire:model="customerName" :label="__('Full name')" required />
                    <flux:input wire:model="customerEmail" type="email" :label="__('Email')" required />
                    <flux:input wire:model="customerPhone" :label="__('Phone')" />

                    @if ($product->type === ProductType::Physical)
                        <flux:separator text="{{ __('Shipping address') }}" />

                        <flux:input wire:model="shippingAddressLine1" :label="__('Address line 1')" required />
                        <flux:input wire:model="shippingAddressLine2" :label="__('Address line 2')" />

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="city" :label="__('City')" required />
                            <flux:input wire:model="state" :label="__('State')" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="postcode" :label="__('Postcode')" required />
                            <flux:input wire:model="country" :label="__('Country')" required />
                        </div>
                    @endif

                    <flux:separator text="{{ __('Have a coupon?') }}" />

                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <flux:input wire:model="couponCode" :label="__('Coupon code')" :disabled="(bool) $appliedCoupon" />
                        </div>
                        @if ($appliedCoupon)
                            <flux:button type="button" wire:click="removeCoupon">{{ __('Remove') }}</flux:button>
                        @else
                            <flux:button type="button" wire:click="applyCoupon">{{ __('Apply') }}</flux:button>
                        @endif
                    </div>

                    @if ($couponError)
                        <flux:error>{{ $couponError }}</flux:error>
                    @endif

                    <div class="space-y-1 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ __('Subtotal') }} ({{ $quantity }} &times; {{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }})</span>
                            <span>{{ $product->currency }} {{ number_format($this->subtotalCents / 100, 2) }}</span>
                        </div>
                        @if ($appliedCoupon)
                            <div class="flex items-center justify-between text-sm text-green-600">
                                <span>{{ __('Discount') }} ({{ $appliedCoupon->code }})</span>
                                <span>-{{ $product->currency }} {{ number_format($this->discountCents / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between font-semibold">
                            <span>{{ __('Total') }}</span>
                            <span>{{ $product->currency }} {{ number_format($this->totalCents / 100, 2) }}</span>
                        </div>
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full" icon:trailing="arrow-right">
                        {{ __('Place order') }}
                    </flux:button>
                </form>
            </flux:card>
        </div>
    </div>
</div>
