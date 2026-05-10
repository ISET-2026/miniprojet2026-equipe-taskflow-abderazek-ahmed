<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
if ($env === 'test') {
    $root = dirname(__DIR__);
    $php = escapeshellarg(PHP_BINARY);
    $console = escapeshellarg($root.'/bin/console');
    passthru("$php $console doctrine:database:drop --env=test --force --if-exists --no-interaction 2>/dev/null");
    passthru("$php $console doctrine:migrations:migrate --env=test --no-interaction");
    passthru("$php $console doctrine:fixtures:load --env=test --no-interaction");
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}
