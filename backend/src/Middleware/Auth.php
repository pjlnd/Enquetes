<?php

namespace App\Middleware;

use App\Helpers\Env;
use App\Helpers\JWT;
use App\Helpers\Response;

class Auth
{
    /**
     * Verifica o header Authorization: Bearer <token>.
     * Se valido, retorna o payload (contendo user_id, name, email).
     * Se invalido, encerra a requisicao com 401.
     */
    public static function check(): array
    {
        $headers = self::getAuthorizationHeader();

        if (!$headers || !str_starts_with($headers, 'Bearer ')) {
            Response::json(['error' => 'Token nao fornecido.'], 401);
            exit;
        }

        $token = trim(substr($headers, 7));
        $secret = Env::get('JWT_SECRET');

        $payload = JWT::decode($token, $secret);

        if (!$payload) {
            Response::json(['error' => 'Token invalido ou expirado.'], 401);
            exit;
        }

        return $payload;
    }

    /**
     * Util em rotas publicas que mudam de comportamento se o usuario estiver logado
     * (ex: listar enquetes mostrando se o usuario ja votou).
     */
    public static function optional(): ?array
    {
        $headers = self::getAuthorizationHeader();

        if (!$headers || !str_starts_with($headers, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($headers, 7));
        $secret = Env::get('JWT_SECRET');

        return JWT::decode($token, $secret);
    }

    private static function getAuthorizationHeader(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
        }

        return null;
    }
}
