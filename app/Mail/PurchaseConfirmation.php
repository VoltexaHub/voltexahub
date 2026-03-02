<?php

namespace App\Mail;

use App\Models\StorePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public StorePurchase $purchase)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Confirmed - ' . $this->purchase->storeItem->name,
        );
    }

    public function content(): Content
    {
        $purchase = $this->purchase->load('storeItem');

        return new Content(
            view: 'emails.purchase_confirmation',
            with: [
                'itemName' => $purchase->storeItem->name,
                'paymentMethod' => $purchase->payment_method === 'credits' ? 'Credits' : 'Credit Card (Stripe)',
                'amount' => $purchase->payment_method === 'credits'
                    ? $purchase->credits_spent . ' credits'
                    : '$' . number_format($purchase->amount_paid, 2),
                'deliveryStatus' => $purchase->delivered_at ? 'Delivered' : 'Pending Delivery',
                'forumUrl' => config('app.frontend_url', 'http://localhost:5173'),
            ],
        );
    }
}
