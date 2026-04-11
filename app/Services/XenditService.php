<?php

namespace App\Services;

use GuzzleHttp\Client;

class XenditService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Create QRIS payment via Payment Request API.
     * Returns full response containing qr_string.
     */
    public function createQrisPayment(string $referenceId, float $amount): array
    {
        $payload = [
            'reference_id' => $referenceId,
            'currency'     => 'IDR',
            'country'      => 'ID',
            'amount'       => $amount,
            'payment_method' => [
                'reference_id' => $referenceId,
                'type'         => 'QR_CODE',
                'reusability'  => 'ONE_TIME_USE',
                'qr_code'      => [
                    'channel_code'       => 'QRIS',
                    'channel_properties' => [
                        'qr_code_generator' => 'INTEGRATED',
                    ],
                ],
            ],
            'checkout_method' => 'ONE_TIME_PAYMENT',
        ];

        return $this->sendPaymentRequest($payload);
    }

    /**
     * Create Virtual Account payment via Payment Request API.
     * Returns full response containing va_number.
     */
    public function createVaPayment(
        string $referenceId,
        float  $amount,
        string $channelCode,
        string $customerName,
    ): array {
        $expiresAt = now()->addHours(24)->toIso8601String();

        $payload = [
            'reference_id' => $referenceId,
            'currency'     => 'IDR',
            'country'      => 'ID',
            'amount'       => $amount,
            'payment_method' => [
                'type'         => 'VIRTUAL_ACCOUNT',
                'reusability'  => 'ONE_TIME_USE',
                'reference_id' => $referenceId,
                'virtual_account' => [
                    'channel_code'       => $channelCode,
                    'channel_properties' => [
                        'customer_name' => $customerName,
                        'expires_at'    => $expiresAt,
                    ],
                ],
            ],
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'metadata' => [
                'source' => 'subscription',
            ],
        ];

        return $this->sendPaymentRequest($payload);
    }

    /**
     * Create E-Wallet payment via Payment Request API.
     * Returns full response containing actions URL for deeplink.
     */
    public function createEwalletPayment(
        string $referenceId,
        float  $amount,
        string $channelCode,
    ): array {
        $payload = [
            'reference_id' => $referenceId,
            'currency'     => 'IDR',
            'country'      => 'ID',
            'amount'       => $amount,
            'payment_method' => [
                'type'         => 'EWALLET',
                'reusability'  => 'ONE_TIME_USE',
                'reference_id' => $referenceId,
                'ewallet'      => [
                    'channel_code'       => $channelCode,
                    'channel_properties' => [
                        'success_return_url' => config('app.url') . '/payment/success',
                        'failure_return_url' => config('app.url') . '/payment/failed',
                    ],
                ],
            ],
            'checkout_method' => 'ONE_TIME_PAYMENT',
        ];

        return $this->sendPaymentRequest($payload);
    }

    /**
     * Send payment request to Xendit API.
     */
    private function sendPaymentRequest(array $payload): array
    {
        $response = $this->client->post('https://api.xendit.co/payment_requests', [
            'auth'    => [config('services.xendit.secret_key'), ''],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }
}
