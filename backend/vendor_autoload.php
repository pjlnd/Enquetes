<?php

/**
 * Autoload minimo, sem depender do composer (que precisa de internet para
 * instalar pacotes). Mapeia o namespace App\ para a pasta src/.
 *
 * Se mais adiante voce rodar `composer init` e `composer require` para usar
 * bibliotecas como firebase/php-jwt ou phpmailer/phpmailer, basta trocar
 * este require, no index.php, por `require __DIR__.'/../vendor/autoload.php';`
 */

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
