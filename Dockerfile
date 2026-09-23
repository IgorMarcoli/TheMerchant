# ==============================================================================
# TheMerchant - Dockerfile de Produção para Render (Web Service)
# PHP 8.2 + Apache + Extensões Essenciais do Laravel
# ==============================================================================

FROM php:8.2-apache

# 1. Instalar dependências do sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# 3. Copiar Composer da imagem oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Diretório de trabalho
WORKDIR /var/www/html

# 5. Configurar VirtualHost do Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# 6. Copiar código da aplicação
COPY . /var/www/html

# 7. Instalar dependências PHP em modo produção
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# 8. Ajustar permissões para o servidor web (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# 9. Copiar e preparar script de inicialização (sanitizando quebras de linha CRLF/LF)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN tr -d '\r' < /usr/local/bin/entrypoint.sh > /usr/local/bin/entrypoint_clean.sh \
    && mv /usr/local/bin/entrypoint_clean.sh /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

# 10. Portas expostas (Render mapeia via variável $PORT)
EXPOSE 80 10000

# 11. Script de inicialização
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
