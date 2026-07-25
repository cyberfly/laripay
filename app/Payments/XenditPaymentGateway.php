<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class XenditPaymentGateway implements PaymentGateway
{
    private const PAID_STATUSES = ['PAID', 'SETTLED'];

    private const FAILED_STATUSES = ['EXPIRED'];

    public function initiate(Order $order): PaymentInitiationResult
    {
        $invoice = Http::withBasicAuth(config('services.xendit.secret_key'), '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $order->order_number,
                'amount' => $this->toMajorUnits($order->total_cents_snapshot),
                'currency' => $order->currency_snapshot,
                'payer_email' => $order->customer_email,
                'description' => "Payment for order {$order->order_number}",
                'success_redirect_url' => route('shop.orders.payment', $order),
                'failure_redirect_url' => route('shop.orders.payment', $order),
            ])
            ->throw()
            ->json();

        $order->forceFill([
            'xendit_invoice_id' => $invoice['id'],
            'xendit_expires_at' => $invoice['expiry_date'] ?? null,
        ])->save();

        return new PaymentInitiationResult(redirectUrl: $invoice['invoice_url']);
    }

    public function handleCallback(Request $request): void
    {
        abort_unless(
            hash_equals((string) config('services.xendit.callback_token'), (string) $request->header('x-callback-token')),
            403,
        );

        $externalId = $request->input('external_id');
        $invoiceId = $request->input('id');
        $status = $request->input('status');

        $order = Order::query()
            ->where('order_number', $externalId)
            ->orWhere('xendit_invoice_id', $invoiceId)
            ->first();

        abort_if($order === null, 404);

        if (! $order->payment_status->isPending()) {
            return;
        }

        if (in_array($status, self::PAID_STATUSES, true)) {
            DB::transaction(function () use ($order, $invoiceId): void {
                $order->forceFill(['payment_reference' => $invoiceId])->save();
                $order->markAsPaid();
            });

            return;
        }

        if (in_array($status, self::FAILED_STATUSES, true)) {
            $order->forceFill(['payment_status' => PaymentStatus::Failed])->save();
        }
    }

    /**
     * Convert a cents-denominated snapshot amount into the major-unit
     * decimal amount Xendit's Invoices API expects (e.g. 150000 cents -> 1500.00).
     * Mirrors the "100 = 1 unit" convention used app-wide for *_cents_snapshot
     * fields and money formatting in the blade views.
     */
    private function toMajorUnits(int $cents): float
    {
        return $cents / 100;
    }
}
