#!/bin/sh
set -e
cd /var/www/html

# Attendre la base de données (même pilote PDO que l'application)
if [ -n "$DB_HOST" ]; then
  echo "Attente de la base $DB_HOST:${DB_PORT:-3306}..."
  i=0
  until ERR=$(php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { fwrite(STDERR, $e->getMessage()); exit(1); }' 2>&1); do
    i=$((i+1))
    echo "  tentative $i: $ERR"
    [ $i -ge 60 ] && echo "Base injoignable" && exit 1
    sleep 2
  done
fi

mkdir -p storage/app/public/candidates storage/app/public/elections storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
         public/election_assets/logos public/election_assets/photos public/candidates/photos
rm -rf public/storage && php artisan storage:link --force || true
chown -R www-data:www-data storage bootstrap/cache public/election_assets public/candidates

# Migrations désactivées par défaut : importer d abord le dump (voir DEPLOIEMENT_COOLIFY.md), puis RUN_MIGRATIONS=true
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
