<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Response;
use App\Middleware\Auth;

class VoteController
{
    /**
     * POST /api/polls/{id}/vote
     * Body: { poll_option_id: number }
     */
    public function store(int $pollId): void
    {
        $payload = Auth::check();
        $data = self::body();

        $optionId = (int) ($data['poll_option_id'] ?? 0);

        if ($optionId <= 0) {
            Response::json(['error' => 'poll_option_id e obrigatorio.'], 422);
            return;
        }

        $db = Database::getConnection();

        // valida se a enquete existe e nao expirou
        $stmt = $db->prepare('SELECT id, expires_at FROM polls WHERE id = ?');
        $stmt->execute([$pollId]);
        $poll = $stmt->fetch();

        if (!$poll) {
            Response::json(['error' => 'Enquete nao encontrada.'], 404);
            return;
        }

        if ($poll['expires_at'] && strtotime($poll['expires_at']) < time()) {
            Response::json(['error' => 'Esta enquete ja foi encerrada.'], 422);
            return;
        }

        // valida se a opcao pertence a enquete
        $stmt = $db->prepare('SELECT id FROM poll_options WHERE id = ? AND poll_id = ?');
        $stmt->execute([$optionId, $pollId]);
        if (!$stmt->fetch()) {
            Response::json(['error' => 'Opcao invalida para esta enquete.'], 422);
            return;
        }

        // valida se o usuario ja votou (a UNIQUE KEY do banco tambem garante isso)
        $stmt = $db->prepare('SELECT id FROM votes WHERE poll_id = ? AND user_id = ?');
        $stmt->execute([$pollId, $payload['user_id']]);
        if ($stmt->fetch()) {
            Response::json(['error' => 'Voce ja votou nesta enquete.'], 409);
            return;
        }

        try {
            $stmt = $db->prepare(
                'INSERT INTO votes (poll_id, poll_option_id, user_id) VALUES (?, ?, ?)'
            );
            $stmt->execute([$pollId, $optionId, $payload['user_id']]);
        } catch (\PDOException $e) {
            // captura a race condition de dois votos simultaneos (UNIQUE KEY)
            Response::json(['error' => 'Voce ja votou nesta enquete.'], 409);
            return;
        }

        // TODO (opcional / diferencial): disparar aqui o envio de email de confirmacao
        // para o votante e notificacao para o dono da enquete (ver PHPMailer no README).

        Response::json(['message' => 'Voto registrado com sucesso.'], 201);
    }

    private static function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
