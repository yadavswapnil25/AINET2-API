#!/bin/bash

# Laravel Deployment Script for VPS
# Run this script after deploying code to your server

set -e  # Exit on any error

PROJECT_DIR="/var/www/AINET2-API"
WEB_USER="www-data"

echo "=========================================="
echo "Laravel Deployment Script"
echo "=========================================="
echo ""

# Navigate to project directory
cd $PROJECT_DIR || exit 1

echo "1. Installing/updating dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "2. Running migrations..."
php artisan migrate --force

echo ""
echo "3. Clearing all caches..."
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear

echo ""
echo "4. Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "5. Creating storage directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo ""
echo "6. Fixing permissions..."
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo ""
echo "7. Creating storage link..."
php artisan storage:link || true

echo ""
echo "8. Fixing OAuth keys permissions (if exist)..."
if [ -f "storage/oauth-private.key" ]; then
    sudo chown $WEB_USER:$WEB_USER storage/oauth-private.key
    sudo chmod 600 storage/oauth-private.key
fi
if [ -f "storage/oauth-public.key" ]; then
    sudo chown $WEB_USER:$WEB_USER storage/oauth-public.key
    sudo chmod 600 storage/oauth-public.key
fi

echo ""
echo "9. Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

echo ""
echo "=========================================="
echo "Deployment completed successfully!"
echo "=========================================="
echo ""
echo "Services restarted:"
echo "  ✓ PHP 8.2-FPM"
echo "  ✓ Nginx"
echo ""

