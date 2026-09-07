<?php

namespace App\Services\Paddle;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaddleService
{
    protected ?string $apiKey;
    protected ?string $clientToken;
    protected ?string $webhookSecret;
    protected string $environment;

    public function __construct()
    {
        $this->apiKey = config('services.paddle.api_key');
        $this->clientToken = config('services.paddle.client_token');
        $this->webhookSecret = config('services.paddle.webhook_secret');
        $this->environment = config('services.paddle.environment', 'sandbox');
    }

    /**
     * Determine if current Paddle environment is sandbox.
     */
    public function isSandbox(): bool
    {
        return strtolower($this->environment) === 'sandbox';
    }

    /**
     * Get the Paddle API base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    /**
     * Get the public client token for Paddle.js frontend.
     */
    public function getClientToken(): ?string
    {
        return $this->clientToken;
    }

    /**
     * Get the environment name (sandbox or live).
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Verify a Paddle Billing webhook signature using raw request body.
     *
     * In Paddle Billing, the Paddle-Signature header has the format:
     * ts=1671552777;h1=a96677f5255...
     * The signature is HMAC-SHA256 of: ts . ":" . rawRequestBody
     *
     * @param string $rawBody Raw, unmodified request body
     * @param string|null $signatureHeader Value of 'Paddle-Signature' header
     * @return bool True if signature is valid and within tolerance window
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (empty($signatureHeader)) {
            Log::warning('[PADDLE WEBHOOK] Missing Paddle-Signature header.');
            return false;
        }

        $secret = $this->webhookSecret;
        if (empty($secret)) {
            Log::error('[PADDLE WEBHOOK] PADDLE_WEBHOOK_SECRET is not configured in environment.');
            return false;
        }

        // Parse key-value components (e.g. ts=12345;h1=abcdef)
        $parts = explode(';', $signatureHeader);
        $parsed = [];
        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $parsed[$kv[0]] = $kv[1];
            }
        }

        if (empty($parsed['ts']) || empty($parsed['h1'])) {
            Log::warning('[PADDLE WEBHOOK] Malformed Paddle-Signature header components.', ['header' => $signatureHeader]);
            return false;
        }

        $timestamp = (int) $parsed['ts'];
        $signature = $parsed['h1'];

        // Maximum allowed drift in seconds (5 minutes)
        $tolerance = 300;
        $currentTime = time();
        if (abs($currentTime - $timestamp) > $tolerance) {
            Log::warning('[PADDLE WEBHOOK] Signature timestamp outside tolerance window.', [
                'header_ts' => $timestamp,
                'current_time' => $currentTime,
                'diff' => abs($currentTime - $timestamp),
            ]);
            return false;
        }

        // Compute HMAC SHA-256 over: "{$timestamp}:{$rawBody}"
        $payloadToSign = "{$timestamp}:{$rawBody}";
        $computedSignature = hash_hmac('sha256', $payloadToSign, $secret);

        if (!hash_equals($computedSignature, $signature)) {
            Log::warning('[PADDLE WEBHOOK] Signature mismatch.');
            return false;
        }

        return true;
    }

    /**
     * Retrieve customer details directly from Paddle API by Customer ID.
     */
    public function getCustomerDetails(string $customerId): ?array
    {
        if (empty($this->apiKey) || empty($customerId)) {
            return null;
        }

        try {
            $url = $this->getBaseUrl() . '/customers/' . urlencode($customerId);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->get($url);

            if ($response->successful()) {
                return $response->json('data');
            }
        } catch (Exception $e) {
            Log::info("[PADDLE SERVICE] getCustomerDetails failed for {$customerId}: " . $e->getMessage());
        }

        return null;
    }
}
