<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorePurchase;
use App\Models\UpgradePurchase;
use App\Models\ForumConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlisioWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $params = $request->all();

        // Verify hash
        $config = json_decode(ForumConfig::where('key', 'payment_providers')->value('value'), true);
        $apiKey = $config['plisio']['secret_key'] ?? $config['plisio']['api_key'] ?? '';

        if (!$this->verifyWebhook($params, $apiKey)) {
            Log::warning('Plisio webhook: invalid hash');
            return response()->json(['error' => 'invalid hash'], 400);
        }

        $status = $params['status'] ?? '';
        $txnId = $params['txn_id'] ?? '';

        if (!in_array($status, ['completed', 'mismatch'])) {
            return response()->json(['ok' => true]);
        }

        // Find store purchase
        $purchase = StorePurchase::where('stripe_payment_intent', $txnId)
            ->where('payment_provider', 'plisio')
            ->where('status', 'pending')
            ->first();

        if ($purchase) {
            $purchase->update(['status' => 'completed', 'delivered_at' => now()]);
            return response()->json(['ok' => true]);
        }

        // Find upgrade purchase
        $upgrade = UpgradePurchase::where('stripe_session_id', $txnId)->first();
        if ($upgrade && $upgrade->status === 'pending') {
            $upgrade->update(['status' => 'completed', 'delivered_at' => now()]);
            $upgrade->load(['user', 'upgradePlan']);
            $user = $upgrade->user;
            $plan = $upgrade->upgradePlan;
            if ($plan->role_name) {
                $user->assignRole($plan->role_name);
            }
            $bonus = $plan->one_time_bonus;
            if ($bonus && !empty($bonus['credits'])) {
                $user->addCredits((int) $bonus['credits'], "Upgrade bonus: {$plan->name}");
            }
        }

        return response()->json(['ok' => true]);
    }

    private function verifyWebhook(array $params, string $apiKey): bool
    {
        if (!isset($params['verify_hash'])) {
            return false;
        }

        $verifyHash = $params['verify_hash'];
        unset($params['verify_hash']);
        ksort($params);

        $hash = md5(implode('', array_values($params)) . $apiKey);

        return hash_equals($hash, $verifyHash);
    }
}
