<?php

namespace App\Services;

use App\Models\ForumConfig;
use App\Models\StorePurchase;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    public function deliverPurchase(StorePurchase $purchase): bool
    {
        $purchase->load(['storeItem.game', 'user']);

        $item = $purchase->storeItem;
        $user = $purchase->user;

        // Currency packs — award credits directly
        if ($item->item_type === 'currency') {
            $amount = (int) $item->item_value;
            if ($amount > 0) {
                $user->addCredits($amount, "Store purchase: {$item->name}", StorePurchase::class, $purchase->id);
            }
            $purchase->update(['delivered_at' => now(), 'status' => 'completed']);
            Log::info("Purchase #{$purchase->id} delivered (currency: +{$amount} credits)");
            return true;
        }

        // Postbit background — apply URL to user profile
        if ($item->item_type === 'postbit_bg') {
            $user->update(['postbit_bg' => $item->item_value ?: null]);
            $purchase->update(['delivered_at' => now(), 'status' => 'completed']);
            Log::info("Purchase #{$purchase->id} delivered (postbit_bg applied)");
            return true;
        }

        // Cosmetics and flairs don't need RCON delivery
        if (in_array($item->item_type, ['cosmetic', 'flair'])) {
            $purchase->update([
                'delivered_at' => now(),
                'status' => 'completed',
            ]);

            Log::info("Purchase #{$purchase->id} delivered (cosmetic/flair, no RCON needed)");
            return true;
        }

        // RCON-delivered items: rank, currency, kit, etc.
        if (! $item->item_value) {
            Log::warning("Purchase #{$purchase->id}: item has no command template (item_value is empty)");
            $purchase->update(['status' => 'failed']);
            return false;
        }

        // Determine game slug for RCON config lookup
        $gameSlug = $item->game?->slug ?? 'minecraft';

        $host = ForumConfig::get("rcon_host_{$gameSlug}");
        $port = (int) ForumConfig::get("rcon_port_{$gameSlug}", 25575);
        $password = ForumConfig::get("rcon_password_{$gameSlug}");

        if (! $host || ! $password) {
            Log::warning("Purchase #{$purchase->id}: RCON not configured for game '{$gameSlug}'");
            $purchase->update(['status' => 'failed']);
            return false;
        }

        // Build command — replace {player} with minecraft IGN or username
        $playerName = match ($gameSlug) {
            'minecraft' => $user->minecraft_verified ? $user->minecraft_ign : $user->username,
            'rust' => $user->rust_verified ? $user->rust_steam_id : $user->username,
            default => $user->username,
        };

        $command = str_replace('{player}', $playerName, $item->item_value);

        try {
            $rcon = new RconService($host, $port, $password);
            $rcon->connect();
            $response = $rcon->sendCommand($command);
            $rcon->disconnect();

            $purchase->update([
                'delivered_at' => now(),
                'status' => 'completed',
            ]);

            Log::info("Purchase #{$purchase->id} delivered via RCON: '{$command}' -> '{$response}'");
            return true;
        } catch (\Throwable $e) {
            Log::error("Purchase #{$purchase->id} RCON delivery failed: {$e->getMessage()}");
            $purchase->update(['status' => 'failed']);
            return false;
        }
    }
}
