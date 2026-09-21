#!/bin/sh
set -e

# Configura porta dinamica do Render (padrao: 80 ou $PORT)
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Garante link simbolico do storage
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

# Limpeza e cache de configuracoes
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
fi

# Execucao automatica de migrations se habilitado no painel do Render
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Executando migrations..."
    php artisan migrate --force || true
fi

# Execucao opcional de seeds se habilitado
if [ "$RUN_SEED" = "true" ]; then
    echo "Executando database seed..."
    php artisan db:seed --force || true
fi

echo "Iniciando Apache na porta $PORT..."
exec apache2-foreground
