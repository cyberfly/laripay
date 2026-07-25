<x-mail::message>
# New Order Received

A new order **{{ $order->order_number }}** has just been placed.

<x-mail::table>
| | |
| :--- | ---: |
| Customer | {{ $order->customer_name }} ({{ $order->customer_email }}) |
| Product | {{ $order->product_name_snapshot }} |
| Quantity | {{ $order->quantity }} |
| Total | {{ $order->currency_snapshot }} {{ number_format($order->total_cents_snapshot / 100, 2) }} |
| Payment method | {{ $order->payment_method }} |
</x-mail::table>

<x-mail::button :url="$adminUrl">
View Order in Admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
