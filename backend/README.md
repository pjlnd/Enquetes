# Backend - Sistema de Enquetes (PHP puro + PDO)

## Como rodar

1. Copie o `.env.example` para `.env` e ajuste os dados do banco:
   ```bash
   cp .env.example .env
   ```

2. Crie o banco rodando o script `database/schema.sql`:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. Suba o servidor embutido do PHP apontando para a pasta `public`:
   ```bash
   php -S localhost:8000 -t public
   ```

4. A API vai responder em `http://localhost:8000/api/...`. Teste:
   ```bash
   curl http://localhost:8000/api/polls
   ```

## Estrutura

```
backend/
├── public/
│   └── index.php      # roteador principal da API
├── src/
│   ├── Controllers/    # AuthController, PollController, VoteController
│   ├── Helpers/        # Database (PDO), Env, JWT, Response
│   └── Middleware/     # Auth (valida o token JWT)
├── database/
│   └── schema.sql
├── vendor_autoload.php # autoload simples (sem composer)
└── .env.example
```

## Rotas da API

| Método | Rota                       | Autenticado? | Descrição                          |
|--------|-----------------------------|:---:|--------------------------------------------|
| POST   | /api/auth/register          | não | Cadastro de usuário                        |
| POST   | /api/auth/login             | não | Login (retorna JWT)                        |
| GET    | /api/auth/me                | sim | Dados do usuário logado                    |
| GET    | /api/polls                  | não | Lista enquetes públicas                    |
| POST   | /api/polls                  | sim | Cria enquete                                |
| GET    | /api/polls/{id}             | não | Detalhe da enquete + opções                |
| PUT    | /api/polls/{id}             | sim | Edita enquete (só o criador)               |
| DELETE | /api/polls/{id}             | sim | Exclui enquete (só o criador)              |
| GET    | /api/polls/{id}/results     | não | Resultado/contagem de votos                |
| POST   | /api/polls/{id}/vote        | sim | Registra o voto do usuário logado          |

Rotas autenticadas exigem o header:
```
Authorization: Bearer <token retornado no login/registro>
```

## Tempo real

O front consulta `/api/polls/{id}/results` a cada poucos segundos enquanto a
tela da enquete está aberta, atualizando a contagem de votos sem precisar de
refresh manual.