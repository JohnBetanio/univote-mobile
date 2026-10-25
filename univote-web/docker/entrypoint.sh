#!/bin/sh
set -e

# Cache configuration, routes, and views for production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start Supervisor (which starts Nginx and PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
