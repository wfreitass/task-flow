# Usando a imagem oficial do PHP 8.1
FROM php:8.1-fpm

# Instalar extensões necessárias.
# pdo_mysql e zip estão em uso. redis e amqp ficam prontas para as integrações futuras
# (cache em Redis e Symfony Messenger com RabbitMQ); OpenSearch é acessado por HTTP e não precisa de extensão.
# O phpredis fica fixado em 6.0.2: o symfony/cache travado no composer.lock (6.4.x) tem um proxy do Redis
# incompatível com o phpredis 6.1+ (erro fatal em Redis6Proxy::hSet). Remova a fixação ao atualizar o symfony/cache.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    librabbitmq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install redis-6.0.2 amqp \
    && docker-php-ext-enable redis amqp \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar o diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos da aplicação
COPY . .

# Instalar dependências do Symfony
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer config --no-plugins allow-plugins.symfony/flex true
RUN composer install --no-interaction --no-scripts

# Expor a porta (PHP-FPM; o acesso HTTP é feito pelo Nginx do docker-compose)
EXPOSE 9000

# Ao iniciar o contêiner, nesta ordem:
#   1. umask 0000: arquivos gerados em runtime (var/, vendor/, chaves) ficam legíveis e graváveis
#      pelo PHP-FPM (www-data), mesmo criados pelo root deste comando, e apagáveis no host;
#   2. cria o .env a partir do .env_example, se não existir;
#   3. instala as dependências, se o volume montado ainda não tiver vendor/;
#   4. espera o MySQL aceitar conexões;
#   5. aplica as migrations do banco;
#   6. gera as chaves JWT (config/jwt/*.pem), se ainda não existirem;
#   7. troca o shell pelo PHP-FPM (exec), que passa a receber os sinais do Docker.
CMD umask 0000; \
    set -e; \
    [ -f .env ] || cp .env_example .env; \
    [ -f vendor/autoload_runtime.php ] || composer install --no-interaction --no-scripts; \
    php docker/wait-for-database.php; \
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; \
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction; \
    exec php-fpm
