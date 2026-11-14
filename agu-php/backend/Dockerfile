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

# Configuração do PHP para a API.
#  - Upload: o padrão (2 MB por arquivo, 8 MB por requisição) barraria os anexos das tarefas. São 5 arquivos de até
#    10 MB por envio; o limite do Nginx (docker-compose.yml) fica um pouco acima de post_max_size.
#  - Erros: nunca na saída. Numa API JSON, um aviso do PHP impresso estraga a resposta e trava o status em 200
#    (acontece, por exemplo, quando o upload passa de post_max_size). Vão para o log do contêiner (stderr).
RUN { \
        echo 'upload_max_filesize=10M'; \
        echo 'post_max_size=55M'; \
        echo 'max_file_uploads=10'; \
        echo 'display_errors=Off'; \
        echo 'log_errors=On'; \
        echo 'error_log=/proc/self/fd/2'; \
    } > /usr/local/etc/php/conf.d/app.ini

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
#   2. libera a escrita em var/ (cache e logs): comandos rodados com outro usuário (ex.: composer pelo uid do host
#      ou como root) deixam arquivos que o www-data, dono do PHP-FPM e do worker, não conseguiria alterar
#      ("Unable to write in the cache directory");
#   3. cria o .env a partir do .env_example, se não existir;
#   4. instala as dependências, se o volume montado ainda não tiver vendor/;
#   5. espera o MySQL aceitar conexões;
#   6. aplica as migrations do banco;
#   7. gera as chaves JWT (config/jwt/*.pem), se ainda não existirem;
#   8. troca o shell pelo PHP-FPM (exec), que passa a receber os sinais do Docker.
CMD umask 0000; \
    set -e; \
    mkdir -p var; chmod -R a+rwX var; \
    [ -f .env ] || cp .env_example .env; \
    [ -f vendor/autoload_runtime.php ] || composer install --no-interaction --no-scripts; \
    php docker/wait-for-database.php; \
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; \
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction; \
    exec php-fpm
