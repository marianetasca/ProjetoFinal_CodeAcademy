# Dockerfile de PRODUÇÃO da API (Laravel).
# Servidor: Apache + PHP (imagem oficial php:8.4-apache). Não usa "php artisan serve",
# que é só para desenvolvimento (para isso existe o Dockerfile.dev).
FROM php:8.4-apache

# Extensões do PHP que o projeto usa (pdo_mysql conversa com o MySQL) e o mod_rewrite do Apache,
# que o Laravel precisa para as rotas funcionarem.
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip bcmath opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# O Apache precisa servir a pasta public/ (nunca a raiz do projeto, onde fica o .env)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# 1) Só as listas de dependências: se elas não mudarem, o Docker reaproveita esta etapa (build mais rápido)
COPY composer.json composer.lock ./
# --no-dev: não instala pacotes de desenvolvimento/teste
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# 2) O código da aplicação
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

# Ao iniciar (aqui as variáveis de ambiente já existem):
#  - storage:link  liga public/storage a storage/app/public (imagens dos produtos)
#  - config:cache e route:cache  deixam a aplicação mais rápida
# Dica: monte um volume em /var/www/html/storage/app/public para os uploads sobreviverem
# à recriação do container, e rode "php artisan migrate --force" no primeiro deploy.
CMD ["sh", "-c", "php artisan storage:link --force; php artisan config:cache && php artisan route:cache && apache2-foreground"]
