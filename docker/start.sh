#!/bin/sh
set -e

cd /app

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "No APP_KEY set — generating one..."
    php artisan key:generate --force
fi

# Wait for MySQL
echo "Waiting for database..."
for i in $(seq 1 30); do
    php -r "
        try {
            new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD')
            );
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null && break
    echo "  attempt $i/30 — not ready yet..."
    sleep 2
done

echo "Database ready."

# Storage symlink
php artisan storage:link --force 2>/dev/null || true

# Migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
