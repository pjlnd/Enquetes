<?php

namespace App\Helpers;

/**
 * Implementacao minima de JWT (HS256), so para nao depender de composer.
 * Para producao, considere trocar por firebase/php-jwt quando tiver
 * acesso a internet no ambiente (composer require firebase/php-jwt).
 */
class JWT
{
    public static function encode(array $payload, string $secret, int $expiresInSeconds): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresInSeconds;

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Retorna o payload decodificado (array) ou null se o token for invalido/expirado.
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSignature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true);
        $expectedSignatureB64 = self::base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedSignatureB64, $signatureB64)) {
            return null; // assinatura invalida
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);

        if (!is_array($payload) || !isset($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null; // expirado
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
