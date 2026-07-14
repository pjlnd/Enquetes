<?php

/**
 * Front controller: todas as requisicoes da API passam por aqui.
 * Configure seu servidor (Apache/Nginx/PHP built-in server) para
 * redirecionar tudo para este arquivo (ver README para os comandos).
 */

require __DIR__ . '/../vendor_autoload.php'; // autoload simples, ver arquivo

use App\Controllers\AuthController;
use App\Controllers\PollController;
use App\Controllers\VoteController;
use App\Helpers\Env;

Env::load(__DIR__ . '/../.env');

// ---- CORS ----
$frontendUrl = Env::get('FRONTEND_URL', '*');
header("Access-Control-Allow-Origin: {$frontendUrl}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Roteamento simples baseado em REQUEST_URI ----
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// remove prefixo caso a API esteja hospedada numa subpasta (ex: /backend/public)
$basePath = '/api';
$path = $uri;
if (str_starts_with($uri, $basePath)) {
    $path = substr($uri, strlen($basePath));
}
$path = '/' . trim($path, '/');

try {
    // ----- Auth -----
    if ($path === '/auth/register' && $method === 'POST') {
        (new AuthController())->register();
        return;
    }
    if ($path === '/auth/login' && $method === 'POST') {
        (new AuthController())->login();
        return;
    }
    if ($path === '/auth/me' && $method === 'GET') {
        (new AuthController())->me();
        return;
    }

    // ----- Polls -----
    if ($path === '/polls' && $method === 'GET') {
        (new PollController())->index();
        return;
    }
    if ($path === '/polls' && $method === 'POST') {
        (new PollController())->store();
        return;
    }
    if (preg_match('#^/polls/(\d+)$#', $path, $m) && $method === 'GET') {
        (new PollController())->show((int) $m[1]);
        return;
    }
    if (preg_match('#^/polls/(\d+)$#', $path, $m) && $method === 'PUT') {
        (new PollController())->update((int) $m[1]);
        return;
    }
    if (preg_match('#^/polls/(\d+)$#', $path, $m) && $method === 'DELETE') {
        (new PollController())->destroy((int) $m[1]);
        return;
    }
    if (preg_match('#^/polls/(\d+)/results$#', $path, $m) && $method === 'GET') {
        (new PollController())->results((int) $m[1]);
        return;
    }

    // ----- Votes -----
    if (preg_match('#^/polls/(\d+)/vote$#', $path, $m) && $method === 'POST') {
        (new VoteController())->store((int) $m[1]);
        return;
    }

    // ----- 404 -----
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Rota nao encontrada.']);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Erro interno.',
        // remova a linha abaixo em producao - so ajuda a debugar em dev
        'debug' => $e->getMessage(),
    ]);
}
