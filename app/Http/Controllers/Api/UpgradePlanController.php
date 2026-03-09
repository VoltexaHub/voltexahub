<?php

namespace App\Http\Controllers\Api;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\UpgradePlan;
use App\Models\UpgradePurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class UpgradePlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = UpgradePlan::with('requiredPlan:id,name,color')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('price')
            ->get();

        return response()->json(['data' => $plans]);
    }

    public function checkout(Request $request, int $id): JsonResponse
    {
        $plan = UpgradePlan::where('is_active', true)->findOrFail($id);

        if ($plan->price <= 0) {
            return response()->json(['message' => 'This plan cannot be purchased with money.'], 422);
        }

        $user = $request->user();
        $stripe = new StripeClient(config('services.stripe.secret'));
        $frontendUrl = config('app.frontend_url', 'https://community.voltexahub.com');

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $plan->name . ' Upgrade',
                        'description' => $plan->description ?? '',
                    ],
                    'unit_amount' => (int) ($plan->price * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/upgrade/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl . '/upgrade',
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'type' => 'upgrade',
            ],
            'customer_email' => $user->email,
        ]);

        UpgradePurchase::create([
            'user_id' => $user->id,
            'upgrade_plan_id' => $plan->id,
            'payment_method' => 'money',
            'amount_paid' => $plan->price,
            'stripe_session_id' => $session->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => ['url' => $session->url],
            'message' => 'Checkout session created.',
        ]);
    }

    public function activate(Request $request, int $id): JsonResponse
    {
        $plan = UpgradePlan::where('is_active', true)->findOrFail($id);

        if ($plan->price > 0) {
            return response()->json(['message' => 'This plan requires payment.'], 422);
        }

        $user = $request->user();

        // Assign role
        if ($plan->role_name) {
            $user->assignRole($plan->role_name);
        }

        // Apply one-time bonus credits
        $bonus = $plan->one_time_bonus;
        if ($bonus && !empty($bonus['credits'])) {
            $user->addCredits((int) $bonus['credits'], "Upgrade bonus: {$plan->name}", UpgradePlan::class, $plan->id);
        }

        UpgradePurchase::create([
            'user_id' => $user->id,
            'upgrade_plan_id' => $plan->id,
            'payment_method' => 'free',
            'status' => 'completed',
            'delivered_at' => now(),
        ]);

        broadcast(new NewNotification($user->id, [
            'type' => 'upgrade_confirmed',
            'title' => 'Upgrade activated',
            'body' => 'Your "' . $plan->name . '" upgrade is now active!',
            'url' => '/upgrade',
        ]));

        return response()->json([
            'message' => 'Upgrade activated successfully.',
        ]);
    }
}
