<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    public function sendNotification($title, $message, $url = null, $data = [], $segments = ['All'])
    {
        // Globally disable OneSignal via config
        if (!config('onesignal.enabled')) {
            Log::info('OneSignal disabled via config. Skipping push notification.');
            return false;
        }

        try {
            $appId = config('onesignal.app_id');
            $apiKey = config('onesignal.rest_api_key');

            if (!$appId || !$apiKey) {
                Log::info('OneSignal not configured (missing app_id or rest_api_key). Skipping push notification.');
                return false;
            }

            $payload = [
                'app_id' => $appId,
                'headings' => ['en' => $title],
                'contents' => ['en' => $message],
                'included_segments' => $segments,
            ];

            if ($url) {
                $payload['url'] = $url;
            }
            if (!empty($data)) {
                $payload['data'] = $data;
            }

            // Optional icons if available
            $logo = config('settings.site_logo');
            if ($logo) {
                $iconUrl = asset('storage/' . $logo);
                $payload['chrome_web_icon'] = $iconUrl;
                $payload['chrome_web_badge'] = $iconUrl;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', $payload);

            if ($response->failed()) {
                Log::error('OneSignal API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('OneSignal Notification Error: ' . $e->getMessage());
            return false;
        }
    }
}

