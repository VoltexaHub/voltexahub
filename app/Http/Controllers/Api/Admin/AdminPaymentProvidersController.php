<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentProvidersController extends Controller
{
    private const ALLOWED_PROVIDERS = ['stripe', 'paypal', 'plisio'];

    private function getProviders(): array
    {
        $raw = ForumConfig::where('key', 'payment_providers')->value('value');

        return $raw ? json_decode($raw, true) : [];
    }

    private function getCustomGateways(): array
    {
        return json_decode(ForumConfig::where('key', 'custom_payment_gateways')->value('value') ?? '[]', true);
    }

    public function index(): JsonResponse
    {
        $providers = $this->getProviders();
        $customSlugs = $this->getCustomGateways();

        foreach ($providers as $slug => &$data) {
            $data['is_custom'] = in_array($slug, $customSlugs);
        }

        return response()->json(['data' => $providers]);
    }

    public function update(Request $request, string $provider): JsonResponse
    {
        $customSlugs = $this->getCustomGateways();
        $isAllowed = in_array($provider, self::ALLOWED_PROVIDERS, true) || in_array($provider, $customSlugs, true);

        if (!$isAllowed) {
            return response()->json(['message' => 'Invalid payment provider.'], 422);
        }

        $providers = $this->getProviders();

        $incoming = $request->validate([
            'enabled'        => ['sometimes', 'boolean'],
            'public_key'     => ['sometimes', 'nullable', 'string'],
            'secret_key'     => ['sometimes', 'nullable', 'string'],
            'webhook_secret' => ['sometimes', 'nullable', 'string'],
            'sandbox'        => ['sometimes', 'boolean'],
            'client_id'      => ['sometimes', 'nullable', 'string'],
            'client_secret'  => ['sometimes', 'nullable', 'string'],
            'api_key'        => ['sometimes', 'nullable', 'string'],
            'store_id'       => ['sometimes', 'nullable', 'string'],
            'merchant_id'    => ['sometimes', 'nullable', 'string'],
            'currencies'     => ['sometimes', 'nullable', 'string'],
        ]);

        $providers[$provider] = array_merge($providers[$provider] ?? [], $incoming);

        ForumConfig::set('payment_providers', json_encode($providers));

        return response()->json([
            'data' => $providers[$provider],
            'message' => ucfirst($provider) . ' settings updated.',
        ]);
    }

    public function updateStoreCurrency(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        ]);

        ForumConfig::set('store_currency', $validated['currency']);

        return response()->json([
            'data' => $validated['currency'],
            'message' => 'Store currency updated.',
        ]);
    }
}
