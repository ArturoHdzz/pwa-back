<?php

namespace App\Services;
use App\Models\WebPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject'    => env('VAPID_SUBJECT'),
                'publicKey'  => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        set_error_handler(function ($errno, $errstr) {
            if (
                $errno === E_USER_WARNING &&
                str_contains($errstr, 'It is highly recommended to install the GMP or BCMath extension')
            ) {
                Log::warning('WebPush: GMP/BCMath no instalados, usando implementación lenta');
                return true; // se traga el warning
            }

            return false; // otros errores que sigan su curso normal
        });

        $this->webPush = new WebPush($auth);

        restore_error_handler();
    }

    public function sendToProfiles(array $profileIds, array $payload): void
    {
        Log::info('entro al servicio');

        $subs = WebPushSubscription::whereIn('profile_id', $profileIds)->get();

        foreach ($subs as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
            ]);

            $this->webPush->queueNotification(
                $subscription,
                json_encode($payload)
            );
        }

        // Enviar de verdad
        foreach ($this->webPush->flush() as $report) {
            // puedes loguear errores si falla alguna
        }
    }
}
