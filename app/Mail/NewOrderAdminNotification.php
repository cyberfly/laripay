<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Order Received - {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.new-admin',
            with: [
                'order' => $this->order,
                'adminUrl' => route('admin.orders.show', $this->order),
            ],
        );
    }
}
