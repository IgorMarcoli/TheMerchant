#!/bin/sh
set -e

# Configura porta dinamica do Render (padrao: 80 ou $PORT)
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# 1. Garante que diretorios do storage e bootstrap existam
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache \
         /var/www/html/database

# 2. Garante que APP_KEY exista para evitar erro 500 por chave de criptografia ausente
if [ -z "$APP_KEY" ]; then
    echo "⚠️ APP_KEY não encontrada no ambiente. Gerando chave temporária de aplicação..."
    export APP_KEY=$(php artisan key:generate --show)
fi

# 3. Define drivers padrao seguros se nao especificados
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

# 4. Suporte a SQLite automatico caso nao haja banco de dados externo configurado
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_CONNECTION

if [ "$DB_CONNECTION" = "sqlite" ]; then
    SQLITE_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    export DB_DATABASE="$SQLITE_PATH"
    SQLITE_DIR="$(dirname "$SQLITE_PATH")"
    mkdir -p "$SQLITE_DIR"
    if [ ! -f "$SQLITE_PATH" ]; then
        echo "📁 Criando arquivo do banco SQLite em $SQLITE_PATH..."
        touch "$SQLITE_PATH"
    fi
    chown -R www-data:www-data "$SQLITE_DIR"
    chmod -R 775 "$SQLITE_DIR"
    chmod 664 "$SQLITE_PATH"
fi

# 5. Ajustar permissoes de arquivos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Garante link simbolico do storage limpo e funcional
rm -rf /var/www/html/public/storage
php artisan storage:link || true
chown -h www-data:www-data /var/www/html/public/storage 2>/dev/null || true

# 7. Limpa caches antigos antes de rodar comandos artisan
php artisan config:clear || true

# 7.1 Servidor WebSocket do chat (Reverb): mesma imagem, servico separado com APP_ROLE=reverb.
# Nao roda Apache, migrations nem seeds. Detalhes em docs/CHAT_TEMPO_REAL.md.
if [ "$APP_ROLE" = "reverb" ]; then
    if [ "$APP_ENV" = "production" ]; then
        php artisan config:cache || true
    fi
    echo "Iniciando Laravel Reverb na porta $PORT..."
    exec runuser -u www-data -- php artisan reverb:start --host=0.0.0.0 --port="$PORT"
fi

# 8. Execucao de migrations (executa por padrao, a menos que RUN_MIGRATIONS=false)
if [ "$RUN_MIGRATIONS" != "false" ]; then
    echo "⚡ Executando migrations do banco de dados..."
    php artisan migrate --force || true
fi

# 9. Execucao de seeds se solicitado ou se banco estiver vazio
if [ "$RUN_SEED" = "true" ]; then
    echo "🌱 Executando database seed..."
    php artisan db:seed --force || true
else
    if [ "$DB_CONNECTION" = "sqlite" ]; then
        USERS_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tr -dc '0-9')
        if [ "$USERS_COUNT" = "0" ] || [ -z "$USERS_COUNT" ]; then
            echo "🌱 Banco SQLite vazio detectado. Executando seed inicial para ambiente de teste..."
            php artisan db:seed --force || true
        fi
    fi
fi

# 10. Cache de configuracoes para producao
if [ "$APP_ENV" = "production" ]; then
    echo "🚀 Otimizando aplicacao para producao (cache)..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# 11. Agendador (expira reservas, reconcilia PIX e reenvia estornos) e worker da fila
if [ "$RUN_SCHEDULER" != "false" ]; then
    echo "⏱️ Iniciando agendador do Laravel (payments:reconcile a cada minuto)..."
    runuser -u www-data -- php artisan schedule:work > /proc/1/fd/1 2>&1 &
fi

if [ "$QUEUE_CONNECTION" != "sync" ] && [ "$RUN_QUEUE_WORKER" != "false" ]; then
    echo "📬 Iniciando worker da fila ($QUEUE_CONNECTION)..."
    runuser -u www-data -- php artisan queue:work --tries=5 --backoff=10 > /proc/1/fd/1 2>&1 &
fi

echo "Iniciando Apache na porta $PORT..."
exec apache2-foreground
