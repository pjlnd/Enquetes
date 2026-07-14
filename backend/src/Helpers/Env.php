<?php

namespace App\Helpers;

/**
 * Carregador simples de arquivos .env (sem depender de composer/vlucas/phpdotenv).
 * Le o arquivo .env na raiz do backend e joga tudo em getenv()/$_ENV.
 */
class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name = trim($name);
            $value = trim($value);

            // remove aspas se existirem
            $value = trim($value, "\"'");

            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }

        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
