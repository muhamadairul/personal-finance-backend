<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FcmService
{
    private string $projectId;
    private string $credentialsPath;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
        $this->credentialsPath = config('services.firebase.credentials');
    }

    /**
     * Send a push notification to a single device.
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error('FCM: Failed to obtain access token');
                return false;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id'         => 'finance_notifications',
                            'default_sound'      => true,
                            'default_vibrate_timings' => true,
                        ],
                    ],
                ],
            ];

            if (!empty($data)) {
                // Data payload must be string key-value pairs
                $message['message']['data'] = array_map('strval', $data);
            }

            $response = Http::withToken($accessToken)
                ->post($url, $message);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM: Send failed', [
                'status'   => $response->status(),
                'body'     => $response->json(),
                'fcmToken' => substr($fcmToken, 0, 20) . '...',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM: Exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send push notification to multiple devices.
     */
    public function sendToDevices(array $fcmTokens, string $title, string $body, array $data = []): int
    {
        $successCount = 0;

        foreach ($fcmTokens as $token) {
            if ($this->sendToDevice($token, $title, $body, $data)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Get OAuth2 access token from Firebase service account.
     * Cached for 50 minutes (tokens are valid for 60 minutes).
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            try {
                $credentials = $this->loadCredentials();

                if (!$credentials || !isset($credentials['private_key'])) {
                    Log::error('FCM: Invalid service account credentials', [
                        'has_env' => !empty(env('FIREBASE_CREDENTIALS_JSON')),
                        'file_exists' => file_exists($this->credentialsPath),
                    ]);
                    return null;
                }

                // Create JWT
                $now = time();
                $header = $this->base64UrlEncode(json_encode([
                    'alg' => 'RS256',
                    'typ' => 'JWT',
                ]));

                $payload = $this->base64UrlEncode(json_encode([
                    'iss'   => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud'   => 'https://oauth2.googleapis.com/token',
                    'iat'   => $now,
                    'exp'   => $now + 3600,
                ]));

                $signatureInput = "$header.$payload";
                $privateKey = openssl_pkey_get_private($credentials['private_key']);

                if (!$privateKey) {
                    Log::error('FCM: Invalid private key in service account');
                    return null;
                }

                openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
                $jwt = $signatureInput . '.' . $this->base64UrlEncode($signature);

                // Exchange JWT for access token
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('FCM: Token exchange failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('FCM: Access token error', ['message' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Load Firebase service account credentials.
     * Priority: 1) FIREBASE_CREDENTIALS_JSON env var (base64-encoded)
     *           2) File path from config
     */
    private function loadCredentials(): ?array
    {
        // 1. Try env var first (for cloud deployments where file isn't available)
        $envJson = env('FIREBASE_CREDENTIALS_JSON');
        if (!empty($envJson)) {
            // Support both raw JSON and base64-encoded JSON
            $decoded = base64_decode($envJson, true);
            if ($decoded !== false) {
                $credentials = json_decode($decoded, true);
                if ($credentials && isset($credentials['private_key'])) {
                    return $credentials;
                }
            }

            // Try as raw JSON
            $credentials = json_decode($envJson, true);
            if ($credentials && isset($credentials['private_key'])) {
                return $credentials;
            }

            Log::warning('FCM: FIREBASE_CREDENTIALS_JSON env var present but invalid');
        }

        // 2. Fall back to file
        if (file_exists($this->credentialsPath)) {
            return json_decode(file_get_contents($this->credentialsPath), true);
        }

        Log::error('FCM: No credentials found (no env var, no file at: ' . $this->credentialsPath . ')');
        return null;
    }

    /**
     * Base64 URL-safe encoding.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
