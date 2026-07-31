<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditPaymentGateway implements PaymentGateway
{
    private const PAID_STATUSES = ['PAID', 'SETTLED'];

    private const FAILED_STATUSES = ['EXPIRED'];

    public function initiate(Order $order): PaymentInitiationResult
    {
        if ($this->hasLiveInvoice($order)) {
            return new PaymentInitiationResult(redirectUrl: $order->xendit_invoice_url);
        }

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
            'xendit_invoice_url' => $invoice['invoice_url'],
            'xendit_expires_at' => $invoice['expiry_date'] ?? null,
        ])->save();

        return new PaymentInitiationResult(redirectUrl: $invoice['invoice_url']);
    }

    public function handleCallback(Request $request): void
    {
        $this->verifyCallbackToken($request);

        $externalId = $request->input('external_id');
        $invoiceId = $request->input('id');
        $status = $request->input('status');
        $amount = $request->input('amount');

        abort_if(blank($externalId) && blank($invoiceId), 422);

        DB::transaction(function () use ($externalId, $invoiceId, $status, $amount): void {
            $order = Order::query()
                ->where(function ($query) use ($externalId, $invoiceId): void {
                    $query
                        ->when(filled($externalId), fn ($query) => $query->orWhere('order_number', $externalId))
                        ->when(filled($invoiceId), fn ($query) => $query->orWhere('xendit_invoice_id', $invoiceId));
                })
                ->lockForUpdate()
                ->first();

            abort_if($order === null, 404);

            if (! $order->payment_status->isPending()) {
                return;
            }

            if (in_array($status, self::PAID_STATUSES, true)) {
                abort_unless($this->amountMatches($order, $amount), 422);

                $order->forceFill(['payment_reference' => $invoiceId])->save();
                $order->markAsPaid();

                return;
            }

            if (in_array($status, self::FAILED_STATUSES, true)) {
                $order->forceFill(['payment_status' => PaymentStatus::Failed])->save();
            }
        });
    }

    /**
     * Determine whether the order already has a Xendit invoice that is still
     * open, so a repeated initiate() reuses it instead of leaving two live
     * invoices — and two payable checkout URLs — against the same order.
     */
    private function hasLiveInvoice(Order $order): bool
    {
        return filled($order->xendit_invoice_id)
            && filled($order->xendit_invoice_url)
            && $order->xendit_expires_at !== null
            && $order->xendit_expires_at->isFuture();
    }

    /**
     * Reject the webhook unless a callback token is configured and the
     * request's `x-callback-token` header matches it exactly. Fails closed:
     * a missing/blank configured token is treated as "reject everything",
     * never as "accept everything" (hash_equals('', '') would otherwise
     * return true for an unconfigured token and an absent header).
     */
    private function verifyCallbackToken(Request $request): void
    {
        $configuredToken = (string) config('services.xendit.callback_token');

        if ($configuredToken === '') {
            Log::error('Xendit callback token is not configured; rejecting webhook request.');

            abort(403);
        }

        abort_unless(hash_equals($configuredToken, (string) $request->header('x-callback-token')), 403);
    }

    /**
     * Verify the invoiced amount reported by Xendit matches what the order
     * was created for, comparing in integer cents to avoid float precision
     * issues, before trusting a PAID status and confirming the order.
     */
    private function amountMatches(Order $order, mixed $amount): bool
    {
        if (! is_numeric($amount)) {
            return false;
        }

        return (int) round(((float) $amount) * 100) === $order->total_cents_snapshot;
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
