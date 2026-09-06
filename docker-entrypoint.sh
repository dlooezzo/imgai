#!/bin/bash
set -e

# Configure Apache port dynamically from Render's $PORT environment variable
# If PORT is not set (e.g. in local testing), default to port 80
PORT="${PORT:-80}"

echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure Laravel storage and bootstrap/cache directories exist
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Set writable permissions for Apache web server user
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create public storage symlink if not already present
if [ ! -L "/var/www/html/public/storage" ] && [ ! -d "/var/www/html/public/storage" ]; then
    echo "Creating public storage symlink..."
    php artisan storage:link || true
fi

# Execute the container command (defaults to apache2-foreground)
exec "$@"
