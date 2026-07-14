<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Response;
use App\Middleware\Auth;

class PollController
{
    /**
     * GET /api/polls
     * Lista todas as enquetes publicas com contagem de votos.
     */
    public function index(): void
    {
        $db = Database::getConnection();
        $currentUser = Auth::optional();

        $stmt = $db->query(
            'SELECT p.id, p.title, p.description, p.expires_at, p.created_at,
                    u.id AS author_id, u.name AS author_name,
                    (SELECT COUNT(*) FROM votes v WHERE v.poll_id = p.id) AS total_votes
             FROM polls p
             JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC'
        );

        $polls = $stmt->fetchAll();

        Response::json(['polls' => $polls]);
    }

    /**
     * GET /api/polls/{id}
     * Detalhe da enquete com opcoes e contagem de votos por opcao.
     */
    public function show(int $id): void
    {
        $db = Database::getConnection();
        $currentUser = Auth::optional();

        $poll = $this->findPollOrFail($db, $id);
        if (!$poll) {
            return;
        }

        $options = $this->getOptionsWithVotes($db, $id);

        $userVote = null;
        if ($currentUser) {
            $stmt = $db->prepare('SELECT poll_option_id FROM votes WHERE poll_id = ? AND user_id = ?');
            $stmt->execute([$id, $currentUser['user_id']]);
            $row = $stmt->fetch();
            $userVote = $row ? (int) $row['poll_option_id'] : null;
        }

        Response::json([
            'poll' => $poll,
            'options' => $options,
            'user_vote' => $userVote,
        ]);
    }

    /**
     * POST /api/polls
     * Cria uma nova enquete (usuario autenticado).
     * Body: { title, description?, expires_at?, options: [string, ...] (2 a 8 itens) }
     */
    public function store(): void
    {
        $payload = Auth::check();
        $data = self::body();

        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '') ?: null;
        $expiresAt = $data['expires_at'] ?? null;
        $options = $data['options'] ?? [];

        if ($title === '') {
            Response::json(['error' => 'O titulo e obrigatorio.'], 422);
            return;
        }

        $options = array_values(array_filter(array_map('trim', $options), fn($o) => $o !== ''));

        if (count($options) < 2 || count($options) > 8) {
            Response::json(['error' => 'A enquete deve ter entre 2 e 8 opcoes.'], 422);
            return;
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                'INSERT INTO polls (user_id, title, description, expires_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$payload['user_id'], $title, $description, $expiresAt ?: null]);

            $pollId = (int) $db->lastInsertId();

            $stmt = $db->prepare('INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)');
            foreach ($options as $optionText) {
                $stmt->execute([$pollId, $optionText]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::json(['error' => 'Erro ao criar enquete.'], 500);
            return;
        }

        Response::json(['message' => 'Enquete criada com sucesso.', 'poll_id' => $pollId], 201);
    }

    /**
     * PUT /api/polls/{id}
     * Edita titulo/descricao/expiracao. Apenas o criador pode editar.
     * (Edicao de opcoes nao esta incluida nesta base para simplificar,
     *  ja que alterar opcoes de uma enquete com votos gera inconsistencia.)
     */
    public function update(int $id): void
    {
        $payload = Auth::check();
        $db = Database::getConnection();

        $poll = $this->findPollOrFail($db, $id);
        if (!$poll) {
            return;
        }

        if ((int) $poll['author_id'] !== (int) $payload['user_id']) {
            Response::json(['error' => 'Voce nao tem permissao para editar esta enquete.'], 403);
            return;
        }

        $data = self::body();
        $title = trim($data['title'] ?? $poll['title']);
        $description = array_key_exists('description', $data) ? trim($data['description']) : $poll['description'];
        $expiresAt = array_key_exists('expires_at', $data) ? $data['expires_at'] : $poll['expires_at'];

        $stmt = $db->prepare('UPDATE polls SET title = ?, description = ?, expires_at = ? WHERE id = ?');
        $stmt->execute([$title, $description ?: null, $expiresAt ?: null, $id]);

        Response::json(['message' => 'Enquete atualizada com sucesso.']);
    }

    /**
     * DELETE /api/polls/{id}
     * Apenas o criador pode excluir.
     */
    public function destroy(int $id): void
    {
        $payload = Auth::check();
        $db = Database::getConnection();

        $poll = $this->findPollOrFail($db, $id);
        if (!$poll) {
            return;
        }

        if ((int) $poll['author_id'] !== (int) $payload['user_id']) {
            Response::json(['error' => 'Voce nao tem permissao para excluir esta enquete.'], 403);
            return;
        }

        $stmt = $db->prepare('DELETE FROM polls WHERE id = ?');
        $stmt->execute([$id]);

        Response::json(['message' => 'Enquete excluida com sucesso.']);
    }

    /**
     * GET /api/polls/{id}/results
     * Usado pelo front tanto na carga inicial quanto (via polling/SSE) para
     * atualizar os resultados em tempo real.
     */
    public function results(int $id): void
    {
        $db = Database::getConnection();

        $poll = $this->findPollOrFail($db, $id);
        if (!$poll) {
            return;
        }

        $options = $this->getOptionsWithVotes($db, $id);

        Response::json([
            'poll_id' => $id,
            'total_votes' => array_sum(array_column($options, 'votes')),
            'options' => $options,
        ]);
    }

    private function findPollOrFail(\PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            'SELECT p.id, p.title, p.description, p.expires_at, p.created_at,
                    p.user_id AS author_id, u.name AS author_name
             FROM polls p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $poll = $stmt->fetch();

        if (!$poll) {
            Response::json(['error' => 'Enquete nao encontrada.'], 404);
            return null;
        }

        return $poll;
    }

    private function getOptionsWithVotes(\PDO $db, int $pollId): array
    {
        $stmt = $db->prepare(
            'SELECT po.id, po.option_text,
                    (SELECT COUNT(*) FROM votes v WHERE v.poll_option_id = po.id) AS votes
             FROM poll_options po
             WHERE po.poll_id = ?
             ORDER BY po.id ASC'
        );
        $stmt->execute([$pollId]);
        return $stmt->fetchAll();
    }

    private static function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
