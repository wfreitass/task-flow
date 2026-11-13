<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// O projeto versiona apenas .env_example; as variáveis podem vir direto do ambiente.
if (method_exists(Dotenv::class, 'bootEnv') && is_file(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

// Trava de segurança: os testes gravam e apagam dados. Só rodam contra um banco cujo nome termina em "_test".
// (A DATABASE_URL tem precedência sobre o "dbname" de config/packages/test/doctrine.yaml.)
$databaseName = ltrim((string) parse_url((string) ($_SERVER['DATABASE_URL'] ?? ''), \PHP_URL_PATH), '/');
if (!str_ends_with($databaseName, '_test')) {
    fwrite(\STDERR, sprintf(
        "Testes abortados: DATABASE_URL aponta para o banco \"%s\". Use um banco de teste (nome terminado em \"_test\"). Veja docs/02-execucao-e-testes.md.\n",
        $databaseName,
    ));
    exit(1);
}
