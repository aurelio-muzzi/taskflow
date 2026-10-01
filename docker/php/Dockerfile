FROM php:8.3-fpm-alpine

# Instalação de dependências de sistema
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# Instalação de extensões PHP essenciais
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip

# Instalação e habilitação da extensão Redis
RUN pecl install redis && docker-php-ext-enable redis

# Instalação do Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Diretório de trabalho da aplicação
WORKDIR /var/www/backend

# Permissões apropriadas para execução segura
RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
