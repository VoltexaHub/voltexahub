<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\ForumConfig;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Stripe\StripeClient;

class PaymentService
{
    protected array $providers;

    public function __construct()
    {
        $raw = ForumConfig::where('key', 'payment_providers')->value('value');
        $this->providers = $raw ? json_decode($raw, true) : [];
    }

    /**
     * Return slugs of all enabled payment providers.
     */
    public function getEnabledProviders(): array
    {
        $enabled = [];
        foreach ($this->providers as $slug => $config) {
            if (!empty($config['enabled'])) {
                $enabled[] = $slug;
            }
        }
        return $enabled;
    }

    /**
     * Return enabled providers with their public keys (no secrets).
     */
    public function getPublicProviders(): array
    {
        $result = [];
        foreach ($this->providers as $slug => $config) {
            if (!empty($config['enabled'])) {
                $result[] = [
                    'slug' => $slug,
                    'public_key' => $config['public_key'] ?? null,
                    'sandbox' => $config['sandbox'] ?? false,
                ];
            }
        }
        return $result;
    }

    /**
     * Create a checkout session with the given provider.
     *
     * @param string $provider Provider slug
     * @param array  $params   Checkout parameters
     * @return array{url: string, session_id: string}
     */
    public function createCheckout(string $provider, array $params): array
    {
        return match ($provider) {
            'stripe' => $this->createStripeCheckout($params),
            'paypal' => $this->createPaypalCheckout($params),
            'plisio' => $this->createPlisioCheckout($params),
            default => $this->createCustomCheckout($provider, $params),
        };
    }

    /**
     * Verify a payment was completed.
     */
    public function verifyPayment(string $provider, string $sessionId): bool
    {
        return match ($provider) {
            'stripe' => $this->verifyStripePayment($sessionId),
            'paypal' => $this->verifyPaypalPayment($sessionId),
            'plisio' => $this->verifyPlisioPayment($sessionId),
            default => $this->verifyCustomPayment($provider, $sessionId),
        };
    }

    // ── Stripe ───────────────────────────────────────────────────────────

    private function createStripeCheckout(array $params): array
    {
        $config = $this->providers['stripe'] ?? [];
        $stripe = new StripeClient($config['secret_key'] ?? '');

        $lineItem = [];

        if (($params['mode'] ?? 'payment') === 'subscription' && !empty($params['stripe_price_id'])) {
            $lineItem = ['price' => $params['stripe_price_id'], 'quantity' => 1];
        } else {
            $storeCurrency = ForumConfig::where('key', 'store_currency')->value('value') ?? 'USD';
            $priceData = [
                'currency' => strtolower($storeCurrency),
                'product_data' => [
                    'name' => $params['name'],
                    'description' => $params['description'] ?? '',
                ],
                'unit_amount' => (int) ($params['amount'] * 100),
            ];

            if (($params['mode'] ?? 'payment') === 'subscription') {
                $priceData['recurring'] = [
                    'interval' => $params['interval'] ?? 'month',
                ];
            }

            $lineItem = ['price_data' => $priceData, 'quantity' => 1];
        }

        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [$lineItem],
            'mode' => $params['mode'] ?? 'payment',
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
            'metadata' => $params['metadata'] ?? [],
            'customer_email' => $params['customer_email'] ?? null,
        ];

        $session = $stripe->checkout->sessions->create($sessionData);

        return [
            'url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    private function verifyStripePayment(string $sessionId): bool
    {
        $config = $this->providers['stripe'] ?? [];
        $stripe = new StripeClient($config['secret_key'] ?? '');

        $session = $stripe->checkout->sessions->retrieve($sessionId);

        return $session->payment_status === 'paid' || $session->status === 'complete';
    }

    // ── PayPal ───────────────────────────────────────────────────────────

    private function getPaypalBaseUrl(): string
    {
        $config = $this->providers['paypal'] ?? [];
        return !empty($config['sandbox'])
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function getPaypalAccessToken(): string
    {
        $config = $this->providers['paypal'] ?? [];
        $baseUrl = $this->getPaypalBaseUrl();

        $response = Http::asForm()
            ->withBasicAuth($config['client_id'] ?? '', $config['client_secret'] ?? '')
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to obtain PayPal access token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    private function createPaypalCheckout(array $params): array
    {
        $baseUrl = $this->getPaypalBaseUrl();
        $token = $this->getPaypalAccessToken();

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => ForumConfig::where('key', 'store_currency')->value('value') ?? 'USD',
                    'value' => number_format($params['amount'], 2, '.', ''),
                ],
                'description' => $params['name'] . ($params['description'] ? ' - ' . $params['description'] : ''),
                'custom_id' => json_encode($params['metadata'] ?? []),
            ]],
            'application_context' => [
                'return_url' => $params['success_url'],
                'cancel_url' => $params['cancel_url'],
                'brand_name' => config('app.name', 'VoltexaHub'),
                'user_action' => 'PAY_NOW',
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$baseUrl}/v2/checkout/orders", $orderData);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to create PayPal order: ' . $response->body());
        }

        $order = $response->json();
        $approvalUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (!$approvalUrl) {
            throw new RuntimeException('PayPal order created but no approval link returned.');
        }

        return [
            'url' => $approvalUrl,
            'session_id' => $order['id'],
        ];
    }

    private function verifyPaypalPayment(string $sessionId): bool
    {
        $baseUrl = $this->getPaypalBaseUrl();
        $token = $this->getPaypalAccessToken();

        $response = Http::withToken($token)
            ->get("{$baseUrl}/v2/checkout/orders/{$sessionId}");

        if (!$response->successful()) {
            return false;
        }

        $status = $response->json('status');
        return in_array($status, ['COMPLETED', 'APPROVED']);
    }

    // ── Plisio ───────────────────────────────────────────────────────────

    public function getProviderConfig(string $slug): array
    {
        return $this->providers[$slug] ?? [];
    }

    private function createPlisioCheckout(array $params): array
    {
        $config = $this->getProviderConfig('plisio');
        $apiKey = $config['secret_key'] ?? $config['api_key'] ?? '';

        $orderId = 'vhub_' . uniqid();

        $storeCurrency = ForumConfig::where('key', 'store_currency')->value('value') ?? 'USD';

        // Determine crypto: explicit param > first from config > BTC fallback
        $crypto = $params['plisio_currency'] ?? null;
        if (!$crypto) {
            $first = strtok($config['currencies'] ?? '', ',');
            $crypto = $first ?: 'BTC';
        }

        $frontendUrl = config('app.frontend_url', 'https://community.voltexahub.com');

        $response = Http::get('https://api.plisio.net/api/v1/invoices/new', [
            'api_key'              => $apiKey,
            'currency'             => $crypto,
            'source_currency'      => $storeCurrency,
            'source_amount'        => number_format($params['amount'], 2, '.', ''),
            'order_number'         => $orderId,
            'order_name'           => $params['name'],
            'callback_url'         => $frontendUrl . '/api/webhooks/plisio',
            'success_callback_url' => $frontendUrl . '/store/success?provider=plisio&order=' . $orderId,
            'fail_callback_url'    => $frontendUrl . '/store/cancel',
            'email'                => $params['customer_email'] ?? '',
        ]);

        $data = $response->json();

        if (!isset($data['status']) || $data['status'] !== 'success') {
            throw new RuntimeException('Plisio error: ' . ($data['data']['message'] ?? 'Unknown error'));
        }

        return [
            'url'        => $data['data']['invoice_url'],
            'session_id' => $data['data']['txn_id'],
        ];
    }

    private function verifyPlisioPayment(string $sessionId): bool
    {
        $config = $this->getProviderConfig('plisio');
        $apiKey = $config['secret_key'] ?? $config['api_key'] ?? '';

        $response = Http::get("https://api.plisio.net/api/v1/operations/{$sessionId}", [
            'api_key' => $apiKey,
        ]);

        $data = $response->json();

        return isset($data['data']['status']) && in_array($data['data']['status'], ['completed', 'mismatch']);
    }

    // ── Custom Gateways ──────────────────────────────────────────────────

    private function loadCustomGateway(string $provider): PaymentGatewayInterface
    {
        $gatewayPath = storage_path("app/payment-gateways/{$provider}.php");
        if (!file_exists($gatewayPath)) {
            throw new RuntimeException("Payment provider not available: {$provider}");
        }

        require_once $gatewayPath;

        $classes = get_declared_classes();
        $config = $this->getProviderConfig($provider);

        foreach (array_reverse($classes) as $class) {
            if (in_array(PaymentGatewayInterface::class, class_implements($class) ?: [])) {
                return new $class($config);
            }
        }

        throw new RuntimeException("Custom gateway class not found for: {$provider}");
    }

    private function createCustomCheckout(string $provider, array $params): array
    {
        $gateway = $this->loadCustomGateway($provider);
        return $gateway->createCheckout($params);
    }

    private function verifyCustomPayment(string $provider, string $sessionId): bool
    {
        $gateway = $this->loadCustomGateway($provider);
        return $gateway->verifyPayment($sessionId);
    }
}
