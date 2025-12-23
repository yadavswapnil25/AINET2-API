#!/bin/bash
# Git post-receive hook for automatic deployment
# Place this file in: /var/www/AINET2-API/.git/hooks/post-receive
# Make it executable: chmod +x .git/hooks/post-receive

# Set working directory
cd /var/www/AINET2-API || exit

# Pull latest changes
echo "Pulling latest changes..."
git pull origin main || git pull origin master

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and cache config
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Fix permissions (requires sudo)
echo "Fixing permissions..."
sudo bash fix-permissions.sh

# Create storage link if it doesn't exist
echo "Creating storage link..."
php artisan storage:link || true

# Check permissions
echo "Verifying permissions..."
php artisan storage:fix-permissions --check || echo "⚠ Some permission issues detected"

echo ""
echo "✅ Deployment completed!"
echo "If you see permission warnings, run: sudo bash fix-permissions.sh"

