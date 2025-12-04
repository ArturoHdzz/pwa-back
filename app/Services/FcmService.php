<?php

namespace App\Services;

class FcmService
{
    public function sendToTokens(array $tokens, array $notification, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $serverKey = config('services.fcm.server_key');

        Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'registration_ids' => $tokens,
            'notification'     => $notification,
            'data'             => $data,
        ]);
    }
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
