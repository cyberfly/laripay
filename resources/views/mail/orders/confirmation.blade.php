<x-mail::message>
# Thank you for your order, {{ $order->customer_name }}!

We've received your order **{{ $order->order_number }}** and it's now being processed.

<x-mail::table>
| | |
| :--- | ---: |
| Product | {{ $order->product_name_snapshot }} |
| Quantity | {{ $order->quantity }} |
| Total | {{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }} |
| Payment method | {{ $order->payment_method }} |
</x-mail::table>

<x-mail::button :url="route('shop.orders.payment', $order)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
