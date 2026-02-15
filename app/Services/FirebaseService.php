<?php

namespace App\Services;

class FirebaseService
{
    public function __construct(private readonly FcmService $fcmService)
    {
    }

    public function sendNotification($title, $body, $token, array $data = [])
    {
        $token = is_string($token) ? trim($token) : '';
        if ($token === '') {
            return;
        }

        $this->fcmService->sendToTokens([$token], (string) $title, (string) $body, $data);
    }
}
