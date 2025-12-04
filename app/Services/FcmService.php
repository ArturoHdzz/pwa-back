<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    protected string $projectId;
    protected string $serviceAccountPath;
    protected string $clientEmail;
    protected string $privateKey;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->serviceAccountPath = base_path(config('services.fcm.service_account'));

        $json = json_decode(file_get_contents($this->serviceAccountPath), true);

        if (! $json || ! isset($json['client_email'], $json['private_key'])) {
            throw new \RuntimeException('Service account JSON inválido o incompleto.');
        }

        $this->clientEmail = $json['client_email'];
        $this->privateKey  = $json['private_key'];
    }

    /**
     * Obtener access_token OAuth2 usando JWT firmado con la cuenta de servicio.
     */
    protected function getAccessToken(): string
    {
        $now  = time();
        $exp  = $now + 3600; // 1 hora
        $scope = 'https://www.googleapis.com/auth/firebase.messaging';
        $aud   = 'https://oauth2.googleapis.com/token';

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss'   => $this->clientEmail,
            'sub'   => $this->clientEmail,
            'scope' => $scope,
            'aud'   => $aud,
            'iat'   => $now,
            'exp'   => $exp,
        ];

        $jwt = $this->encodeJwt($header, $payload, $this->privateKey);

        $response = Http::asForm()
    ->withoutVerifying() // 👈 desactiva la validación SSL (SOLO DEV)
    ->post($aud, [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]);


        if (! $response->successful()) {
            throw new \RuntimeException('Error obteniendo access_token de Google: ' . $response->body());
        }

        $data = $response->json();
        if (! isset($data['access_token'])) {
            throw new \RuntimeException('Respuesta sin access_token: ' . $response->body());
        }

        return $data['access_token'];
    }

    /**
     * Enviar notificación a varios device tokens.
     * v1 no soporta registration_ids, así que mandamos 1 request por token.
     */
    public function sendToTokens(array $tokens, array $notification, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $accessToken = $this->getAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        foreach ($tokens as $token) {
            Http::withToken($accessToken)
                ->post($url, [
                    'message' => [
                        'token'        => $token,
                        'notification' => $notification,
                        'data'         => $data,
                    ],
                ])
                ->throw(); // si FCM responde error → lanza excepción
        }
    }

    /**
     * Construye un JWT RS256 (header.payload.firma).
     */
    protected function encodeJwt(array $header, array $payload, string $privateKey): string
    {
        $segments = [];

        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);

        $signature = '';
        $success = openssl_sign(
            $signingInput,
            $signature,
            $privateKey,
            'sha256'
        );

        if (! $success) {
            throw new \RuntimeException('No se pudo firmar el JWT con openssl.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

