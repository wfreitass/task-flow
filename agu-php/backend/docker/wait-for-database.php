<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
if (!is_string($databaseUrl) || $databaseUrl === '') {
    fwrite(STDERR, "DATABASE_URL não configurada.\n");
    exit(1);
}

// Quinze tentativas, timeout de conexão de 2s e pausa de 1s: abaixo de 60s.
// A URL e a mensagem da exceção nunca são impressas, pois podem conter segredos.
for ($attempt = 1; $attempt <= 15; ++$attempt) {
    $connection = null;
    try {
        $connection = DriverManager::getConnection([
            'url' => $databaseUrl,
            'driverOptions' => [PDO::ATTR_TIMEOUT => 2],
        ]);
        $connection->executeQuery('SELECT 1')->fetchOne();
        $connection->close();
        fwrite(STDOUT, "MySQL pronto; iniciando migrations.\n");
        exit(0);
    } catch (Throwable $exception) {
        if ($connection !== null) {
            $connection->close();
        }
        if ($attempt === 1) {
            fwrite(STDERR, "Aguardando conexão ao MySQL (até 15 tentativas).\n");
        }
        if ($attempt < 15) {
            sleep(1);
        }
    }
}

fwrite(STDERR, "MySQL indisponível após 15 tentativas; verifique host, banco e credenciais.\n");
exit(1);
