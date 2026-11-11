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

exec "$@"
