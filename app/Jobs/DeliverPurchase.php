<?php

namespace App\Jobs;

use App\Models\StorePurchase;
use App\Services\DeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public StorePurchase $purchase)
    {
    }

    public function handle(DeliveryService $deliveryService): void
    {
        $deliveryService->deliverPurchase($this->purchase);
    }

    public function failed(\Throwable $exception): void
    {
        $this->purchase->update(['status' => 'failed']);

        Log::error("DeliverPurchase job failed for purchase #{$this->purchase->id}: {$exception->getMessage()}");
    }
}
