<?php

namespace App\Http\Controllers\Api;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\UpgradePlan;
use App\Models\UpgradePurchase;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $request->validate([
            'provider' => ['sometimes', 'string'],
            'plisio_currency' => ['sometimes', 'string', 'max:10'],
        ]);

        $user = $request->user();
        $service = new PaymentService();
        $providers = $service->getEnabledProviders();
        $provider = $request->input('provider', $providers[0] ?? 'stripe');

        if (!in_array($provider, $providers)) {
            return response()->json(['message' => 'Payment provider not available.'], 422);
        }

        $frontendUrl = config('app.frontend_url', 'https://community.voltexahub.com');

        // Determine mode and interval based on plan term
        $mode = 'payment';
        $interval = null;

        if ($plan->term === 'monthly') {
            $mode = 'subscription';
            $interval = 'month';
        } elseif ($plan->term === 'yearly') {
            $mode = 'subscription';
            $interval = 'year';
        }

        $params = [
            'name' => $plan->name . ' Upgrade',
            'description' => $plan->description ?? '',
            'amount' => (float) $plan->price,
            'mode' => $mode,
            'success_url' => $frontendUrl . '/upgrade/success?session_id={CHECKOUT_SESSION_ID}&provider=' . $provider,
            'cancel_url' => $frontendUrl . '/upgrade',
            'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id, 'type' => 'upgrade'],
            'customer_email' => $user->email,
        ];

        if ($interval) {
            $params['interval'] = $interval;
        }

        if ($plan->stripe_price_id) {
            $params['stripe_price_id'] = $plan->stripe_price_id;
        }

        if ($request->filled('plisio_currency')) {
            $params['plisio_currency'] = $request->input('plisio_currency');
        }

        $result = $service->createCheckout($provider, $params);

        UpgradePurchase::create([
            'user_id' => $user->id,
            'upgrade_plan_id' => $plan->id,
            'payment_method' => 'money',
            'amount_paid' => $plan->price,
            'stripe_session_id' => $result['session_id'],
            'status' => 'pending',
            'payment_provider' => $provider,
        ]);

        return response()->json([
            'data' => ['url' => $result['url'], 'session_id' => $result['session_id'], 'provider' => $provider],
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
