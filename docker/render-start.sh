#!/bin/sh
# Inicializacao do container no Render: prepara o banco e sobe o Apache.
set -e

cd /var/www/html

echo "[start] Aplicando migrations..."
php artisan migrate --force

echo "[start] Verificando dados iniciais..."
php artisan db:seed-if-empty

exec apache2-foreground
