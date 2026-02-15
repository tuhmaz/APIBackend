<?php

namespace App\Services;

use App\Models\PushToken;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.fcm.enabled', false);
    }

    public function sendToAllUsers(string $title, string $body, array $data = []): void
    {
        $tokens = PushToken::query()->pluck('token')->all();
        $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $tokens = $this->sanitizeTokens($tokens);
        if (empty($tokens)) {
            return;
        }

        $projectId = trim((string) config('services.fcm.project_id'));
        if ($projectId === '') {
            Log::warning('FCM project_id is not configured.');
            return;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return;
        }

        $payloadData = $this->normalizeData($data);
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $invalidTokens = [];

        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($endpoint, [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $payloadData,
                            'android' => [
                                'priority' => 'HIGH',
                                'notification' => [
                                    'sound' => 'default',
                                ],
                            ],
                            'apns' => [
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if (!$response->failed()) {
                    continue;
                }

                if ($this->isInvalidTokenResponse($response->json(), $response->body())) {
                    $invalidTokens[] = $token;
                }

                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'token_suffix' => substr($token, -12),
                    'response' => $response->json() ?: $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('FCM send exception', [
                    'token_suffix' => substr($token, -12),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($invalidTokens)) {
            PushToken::query()
                ->whereIn('token', array_values(array_unique($invalidTokens)))
                ->delete();
        }
    }

    private function sanitizeTokens(array $tokens): array
    {
        $clean = [];

        foreach ($tokens as $token) {
            if (!is_string($token)) {
                continue;
            }

            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $clean[$token] = true;
        }

        return array_keys($clean);
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $stringKey = (string) $key;

            if (is_scalar($value)) {
                $normalized[$stringKey] = (string) $value;
                continue;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $normalized[$stringKey] = $encoded;
            }
        }

        return $normalized;
    }

    private function getAccessToken(): ?string
    {
        $cached = Cache::get('fcm.oauth.access_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $this->requestAccessToken();
        if (!$token) {
            return null;
        }

        Cache::put('fcm.oauth.access_token', $token, now()->addMinutes(50));

        return $token;
    }

    private function requestAccessToken(): ?string
    {
        $serviceAccount = $this->loadServiceAccount();
        if (!$serviceAccount) {
            return null;
        }

        $clientEmail = trim((string) ($serviceAccount['client_email'] ?? ''));
        $privateKey = (string) ($serviceAccount['private_key'] ?? '');
        $tokenUri = trim((string) ($serviceAccount['token_uri'] ?? config('services.fcm.token_uri', 'https://oauth2.googleapis.com/token')));

        if ($clientEmail === '' || $privateKey === '' || $tokenUri === '') {
            Log::warning('FCM service account is missing required fields.');
            return null;
        }

        try {
            $now = time();
            $jwt = JWT::encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ], $privateKey, 'RS256');

            $response = Http::asForm()
                ->acceptJson()
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if ($response->failed()) {
                Log::warning('Failed to get FCM OAuth token', [
                    'status' => $response->status(),
                    'response' => $response->json() ?: $response->body(),
                ]);
                return null;
            }

            $token = (string) $response->json('access_token', '');
            if ($token === '') {
                Log::warning('FCM OAuth response does not contain access_token.');
                return null;
            }

            return $token;
        } catch (\Throwable $e) {
            Log::warning('FCM OAuth token generation failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function loadServiceAccount(): ?array
    {
        $path = trim((string) config('services.fcm.service_account_file'));
        if ($path === '') {
            Log::warning('FCM service account file path is not configured.');
            return null;
        }

        if (!is_file($path)) {
            Log::warning('FCM service account file not found.', ['path' => $path]);
            return null;
        }

        try {
            $raw = file_get_contents($path);
            if ($raw === false || trim($raw) === '') {
                Log::warning('FCM service account file is empty.', ['path' => $path]);
                return null;
            }

            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('FCM service account parsing failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function isInvalidTokenResponse($jsonBody, string $rawBody): bool
    {
        $blob = strtoupper($rawBody);

        if (is_array($jsonBody)) {
            $encoded = json_encode($jsonBody);
            if ($encoded !== false) {
                $blob .= ' ' . strtoupper($encoded);
            }
        }

        return str_contains($blob, 'UNREGISTERED')
            || str_contains($blob, 'INVALID_ARGUMENT')
            || str_contains($blob, 'NOT_FOUND');
    }
}
