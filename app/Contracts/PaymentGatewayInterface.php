<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /** Return the gateway display name, e.g. "My Custom Gateway" */
    public function getName(): string;

    /**
     * Create a checkout session. $params contains:
     *   name, description, amount (float USD), success_url, cancel_url,
     *   customer_email, metadata (array)
     * Must return: ["url" => "...", "session_id" => "..."]
     */
    public function createCheckout(array $params): array;

    /**
     * Verify a payment by session_id. Return true if paid.
     */
    public function verifyPayment(string $sessionId): bool;
}
