<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Env;
use App\Helpers\JWT;
use App\Helpers\Response;
use App\Middleware\Auth;

class AuthController
{
    public function register(): void
    {
        $data = self::body();

        $name = trim($data['name'] ?? '');
        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            Response::json(['error' => 'Nome, email e senha sao obrigatorios.'], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'Email invalido.'], 422);
            return;
        }

        if (strlen($password) < 6) {
            Response::json(['error' => 'A senha deve ter pelo menos 6 caracteres.'], 422);
            return;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            Response::json(['error' => 'Ja existe uma conta com esse email.'], 409);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);

        $userId = (int) $db->lastInsertId();

        $token = self::generateToken($userId, $name, $email);

        Response::json([
            'token' => $token,
            'user' => ['id' => $userId, 'name' => $name, 'email' => $email],
        ], 201);
    }

    public function login(): void
    {
        $data = self::body();

        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Email e senha sao obrigatorios.'], 422);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::json(['error' => 'Email ou senha invalidos.'], 401);
            return;
        }

        $token = self::generateToken((int) $user['id'], $user['name'], $user['email']);

        Response::json([
            'token' => $token,
            'user' => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']],
        ]);
    }

    public function me(): void
    {
        $payload = Auth::check();

        Response::json([
            'user' => [
                'id' => $payload['user_id'],
                'name' => $payload['name'],
                'email' => $payload['email'],
            ],
        ]);
    }

    private static function generateToken(int $userId, string $name, string $email): string
    {
        $secret = Env::get('JWT_SECRET');
        $expiration = (int) Env::get('JWT_EXPIRATION', 86400);

        return JWT::encode([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
        ], $secret, $expiration);
    }

    private static function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
