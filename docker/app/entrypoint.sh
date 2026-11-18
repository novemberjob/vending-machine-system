#!/bin/sh
set -e

cd /var/www/html

# The real .env is git-ignored; create a default one on first boot.
if [ ! -f .env ]; then
    cp .env.example .env
fi

DB_HOST="${DB_HOST:-db}"
DB_NAME="${DB_NAME:-vms}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-rootpass}"

echo "Waiting for MySQL at ${DB_HOST}..."
until mysqladmin ping -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASS" --silent >/dev/null 2>&1; do
    sleep 2
done

echo "Preparing database ${DB_NAME}..."
mysql -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASS" \
    -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Both migrations are idempotent, so this is safe on every container start.
mysql -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASS" "$DB_NAME" < migrations/001_schema.sql
mysql -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASS" "$DB_NAME" < migrations/002_seed.sql

echo "Seeding admin user..."
php scripts/seed_admin.php \
    "${ADMIN_NAME:-Admin}" \
    "${ADMIN_EMAIL:-admin@example.com}" \
    "${ADMIN_PASSWORD:-admin12345}"

# AL2023 normally creates this via its systemd unit; in a container we must do it ourselves.
mkdir -p /run/php-fpm

echo "Starting PHP-FPM..."
exec "$@"
