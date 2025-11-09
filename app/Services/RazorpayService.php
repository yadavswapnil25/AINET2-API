<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class RazorpayService
{
    protected string $baseUrl = 'https://api.razorpay.com/v1/';

    protected string $keyId;

    protected string $keySecret;

    public function __construct()
    {
        $this->keyId = "rzp_live_Rdf4DeHJQIk0Wz";
        $this->keySecret = "SMWAgRIif0TEeClV9ygiTzgS";

        if (!$this->keyId || !$this->keySecret) {
            throw new RuntimeException('Razorpay credentials are not configured.');
        }
    }

    /**
     * Create a Razorpay order with auto-capture enabled.
     *
     * @param  int|float  $amount  Amount in rupees
     * @param  string  $currency
     * @param  string|null  $receipt
     * @param  array<string,string>  $notes
     * @return array<string,mixed>
     */
    public function createOrder(int|float $amount, string $currency = 'INR', ?string $receipt = null, array $notes = []): array
    {
        $payload = [
            'amount' => (int) round(((float) $amount) * 100), // amount in paise
            'currency' => $currency,
            'receipt' => $receipt ?: Str::uuid()->toString(),
            'payment_capture' => 1, // auto-capture enabled
            'notes' => $notes,
        ];

        $response = $this->request('orders', 'post', $payload);

        return $response->json();
    }

    /**
     * Fetch a payment by its ID.
     *
     * @return array<string,mixed>
     */
    public function fetchPayment(string $paymentId): array
    {
        $response = $this->request("payments/{$paymentId}", 'get');

        return $response->json();
    }

    /**
     * Capture an authorised payment.
     *
     * @param  string  $paymentId
     * @param  int  $amount  Amount in paise
     * @return array<string,mixed>
     */
    public function capturePayment(string $paymentId, int $amount): array
    {
        $payload = [
            'amount' => $amount,
        ];

        $response = $this->request("payments/{$paymentId}/capture", 'post', $payload);

        return $response->json();
    }

    /**
     * Refund a payment.
     *
     * @param  string  $paymentId
     * @param  int|null  $amount Amount in paise (optional)
     * @return array<string,mixed>
     */
    public function refundPayment(string $paymentId, ?int $amount = null): array
    {
        $payload = [];

        if ($amount !== null) {
            $payload['amount'] = $amount;
        }

        $response = $this->request("payments/{$paymentId}/refund", 'post', $payload);

        return $response->json();
    }

    /**
     * Verify the Razorpay signature.
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $payload = $orderId . '|' . $paymentId;
        $generatedSignature = hash_hmac('sha256', $payload, $this->keySecret);

        return hash_equals($generatedSignature, $signature);
    }

    protected function request(string $uri, string $method = 'get', array $payload = []): Response
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->acceptJson()
            ->{$method}($this->baseUrl . ltrim($uri, '/'), $payload);

        if ($response->failed()) {
            $message = $response->json('error.description') ?? $response->body();
            throw new RuntimeException("Razorpay API call failed: {$message}");
        }

        return $response;
    }
}


