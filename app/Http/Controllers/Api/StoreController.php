<?php

namespace App\Http\Controllers\Api;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Mail\PurchaseConfirmation;
use App\Models\StoreItem;
use App\Models\StorePurchase;
use App\Models\UserCosmetic;
use App\Notifications\PurchaseConfirmedNotification;
use App\Models\ForumConfig;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class StoreController extends Controller
{
    public function index(): JsonResponse
    {
        $items = StoreItem::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function providers(): JsonResponse
    {
        $service = new PaymentService();
        return response()->json(['data' => $service->getEnabledProviders()]);
    }

    public function currency(): JsonResponse
    {
        $currency = ForumConfig::where('key', 'store_currency')->value('value') ?? 'USD';
        return response()->json(['data' => $currency]);
    }

    public function plisioCurrencies(): JsonResponse
    {
        $raw = ForumConfig::where('key', 'payment_providers')->value('value');
        $providers = json_decode($raw ?? '{}', true);
        $currencies = array_values(array_filter(array_map('trim', explode(',', $providers['plisio']['currencies'] ?? 'BTC,ETH,LTC,USDT,TRX,DOGE'))));
        return response()->json(['data' => $currencies]);
    }

    public function purchaseWithCredits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_item_id' => ['required', 'exists:store_items,id'],
        ]);

        $item = StoreItem::findOrFail($validated['store_item_id']);
        $user = $request->user();

        if (! $item->price_credits) {
            return response()->json([
                'message' => 'This item cannot be purchased with credits.',
            ], 422);
        }

        if ($user->credits < $item->price_credits) {
            return response()->json([
                'message' => 'Insufficient credits.',
            ], 422);
        }

        $user->spendCredits($item->price_credits, "Purchased: {$item->name}", StoreItem::class, $item->id);

        $purchase = StorePurchase::create([
            'user_id' => $user->id,
            'store_item_id' => $item->id,
            'payment_method' => 'credits',
            'credits_spent' => $item->price_credits,
            'status' => 'completed',
            'delivered_at' => now(),
        ]);

        // If it's a cosmetic, add to user's cosmetics
        if (in_array($item->item_type, ['cosmetic', 'flair'])) {
            UserCosmetic::create([
                'user_id' => $user->id,
                'store_item_id' => $item->id,
                'is_active' => true,
                'activated_at' => now(),
            ]);
        }

        // If it's an XP boost, create or extend boost
        if ($item->item_type === 'xp_boost') {
            $config = json_decode($item->item_value, true);
            $multiplier = (float) ($config['multiplier'] ?? 2.0);
            $durationHours = (int) ($config['duration_hours'] ?? 1);

            $existingBoost = \App\Models\UserXpBoost::where('user_id', $user->id)
                ->where('expires_at', '>', now())
                ->first();

            if ($existingBoost) {
                $existingBoost->expires_at = $existingBoost->expires_at->addHours($durationHours);
                $existingBoost->save();
            } else {
                \App\Models\UserXpBoost::create([
                    'user_id' => $user->id,
                    'multiplier' => $multiplier,
                    'expires_at' => now()->addHours($durationHours),
                ]);
            }
        }

        // Send purchase confirmation email
        Mail::to($user)->send(new PurchaseConfirmation($purchase));

        // Dispatch delivery job for RCON items

        $user->notify(new PurchaseConfirmedNotification($purchase));
        broadcast(new NewNotification($user->id, [
            'type' => 'purchase_confirmed',
            'title' => 'Purchase confirmed',
            'body' => 'Your purchase of "' . $item->name . '" was successful',
            'url' => '/store',
        ]));
        $user->checkAchievements();

        return response()->json([
            'data' => $purchase->load('storeItem'),
            'message' => 'Purchase successful.',
        ], 201);
    }

    public function createCheckout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_item_id' => ['required', 'exists:store_items,id'],
            'provider' => ['sometimes', 'string'],
            'plisio_currency' => ['sometimes', 'string', 'max:10'],
        ]);

        $item = StoreItem::findOrFail($validated['store_item_id']);

        if (! $item->price_money) {
            return response()->json([
                'message' => 'This item cannot be purchased with money.',
            ], 422);
        }

        $user = $request->user();
        $service = new PaymentService();
        $providers = $service->getEnabledProviders();
        $provider = $request->input('provider', $providers[0] ?? 'stripe');

        if (!in_array($provider, $providers)) {
            return response()->json(['message' => 'Payment provider not available.'], 422);
        }

        $frontendUrl = config('app.frontend_url', 'https://community.voltexahub.com');

        $checkoutParams = [
            'name' => $item->name,
            'description' => $item->description ?? '',
            'amount' => (float) $item->price_money,
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/store/success?session_id={CHECKOUT_SESSION_ID}&provider=' . $provider,
            'cancel_url' => $frontendUrl . '/store/cancel',
            'metadata' => ['user_id' => $user->id, 'item_id' => $item->id, 'type' => 'store'],
            'customer_email' => $user->email,
        ];

        if (!empty($validated['plisio_currency'])) {
            $checkoutParams['plisio_currency'] = $validated['plisio_currency'];
        }

        $result = $service->createCheckout($provider, $checkoutParams);

        $purchase = StorePurchase::create([
            'user_id' => $user->id,
            'store_item_id' => $item->id,
            'payment_method' => 'money',
            'amount_paid' => $item->price_money,
            'status' => 'pending',
            'stripe_payment_intent' => $result['session_id'],
            'payment_provider' => $provider,
        ]);

        return response()->json([
            'data' => ['url' => $result['url'], 'session_id' => $result['session_id'], 'provider' => $provider],
            'message' => 'Checkout session created.',
        ]);
    }
}
