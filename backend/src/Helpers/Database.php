<?php

namespace App\Helpers;

use PDO;
use PDOException;

/**
 * Conexao unica (singleton) com o banco via PDO.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', '3306');
            $dbname = Env::get('DB_NAME', 'enquetes');
            $user = Env::get('DB_USER', 'root');
            $pass = Env::get('DB_PASS', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // ajuda a prevenir SQL Injection
                ]);
            } catch (PDOException $e) {
                Response::json(['error' => 'Falha ao conectar no banco de dados.'], 500);
                exit;
            }
        }

        return self::$instance;
    }
}
