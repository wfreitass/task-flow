#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env_example .env
fi

if [ ! -f vendor/autoload_runtime.php ]; then
    composer install --no-interaction --no-scripts
fi

php docker/wait-for-database.php
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Chaves JWT ficam fora do repositório (config/jwt/*.pem); gera apenas se ainda não existirem.
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

exec "$@"
