<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Order $order, string $token): StreamedResponse|RedirectResponse
    {
        abort_unless(hash_equals((string) $order->digital_access_token, $token), 404);
        abort_unless($order->payment_status === PaymentStatus::Paid, 403);
        abort_unless($order->product_type_snapshot === ProductType::Digital->value, 404);

        $product = $order->product;

        if ($product?->digital_access_url) {
            return redirect()->away($product->digital_access_url);
        }

        abort_unless($product?->digital_file_path && Storage::disk('public')->exists($product->digital_file_path), 404);

        return Storage::disk('public')->download($product->digital_file_path);
    }
}
