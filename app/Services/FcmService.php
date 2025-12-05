<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmService
{
    protected string $projectId;
    protected string $serviceAccountPath;
    protected string $clientEmail;
    protected string $privateKey;

    public function __construct()
    {
        $this->projectId = env('FCM_PROJECT_ID');
        $this->serviceAccountPath = base_path(env('FCM_SERVICE_ACCOUNT'));

        if (! $this->projectId || ! $this->serviceAccountPath || ! file_exists($this->serviceAccountPath)) {
            throw new RuntimeException('Config de FCM incompleta.');
        }

        $json = json_decode(file_get_contents($this->serviceAccountPath), true);
        if (! $json || ! isset($json['client_email'], $json['private_key'])) {
            throw new RuntimeException('Service account JSON inválido o incompleto.');
        }

        $this->clientEmail = $json['client_email'];
        $this->privateKey  = $json['private_key'];
    }

    /**
     * Envía una notificación a varios tokens FCM.
     *
     * @param  string[]  $tokens
     * @param  array     $notification ['title' => '...', 'body' => '...']
     * @param  array     $data         datos extra para el SW/app
     */
    public function sendToTokens(array $tokens, array $notification, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $accessToken = $this->getAccessToken();

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $notification['title'] ?? '',
                        'body'  => $notification['body'] ?? '',
                    ],
                    'data' => array_map('strval', $data),
                ],
            ];

            $resp = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", $payload);

            if (! $resp->successful()) {
                // Aquí puedes loguear errores si quieres
                \Log::warning('Error enviando FCM', [
                    'token' => $token,
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
            }
        }
    }

    /**
     * Obtiene un access_token OAuth2 firmando un JWT con la service account.
     */
    protected function getAccessToken(): string
    {
        $now   = time();
        $exp   = $now + 3600;

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss'   => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $exp,
        ];

        $jwt = $this->encodeJwt($header, $payload, $this->privateKey);

        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (! $resp->successful()) {
            throw new RuntimeException('No se pudo obtener access_token de FCM: '.$resp->body());
        }

        return $resp->json('access_token');
    }

    protected function encodeJwt(array $header, array $payload, string $privateKey): string
    {
        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);
        openssl_sign($signingInput, $signature, $privateKey, 'sha256');

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
