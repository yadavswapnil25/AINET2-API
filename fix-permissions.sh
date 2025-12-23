#!/bin/bash

# Fix Laravel Storage and Cache Permissions
# Run this script on your VPS server after deployment

# Navigate to your Laravel project directory
cd /var/www/AINET2-API || exit 1

# Set ownership to www-data (or your web server user)
# Replace www-data with your web server user if different (nginx, apache, etc.)
WEB_USER="www-data"

echo "Fixing Laravel storage and cache permissions..."
echo "Web server user: $WEB_USER"
echo ""

# Create storage subdirectories if they don't exist
sudo mkdir -p storage/app/public
sudo mkdir -p storage/framework/cache
sudo mkdir -p storage/framework/sessions
sudo mkdir -p storage/framework/testing
sudo mkdir -p storage/framework/views
sudo mkdir -p storage/logs
sudo mkdir -p bootstrap/cache

# Set ownership recursively
echo "Setting ownership to $WEB_USER..."
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache

# Set directory permissions (775 = rwxrwxr-x - owner and group can write)
echo "Setting directory permissions..."
sudo find storage -type d -exec chmod 775 {} \;
sudo find bootstrap/cache -type d -exec chmod 775 {} \;

# Set file permissions (664 = rw-rw-r-- - owner and group can write)
echo "Setting file permissions..."
sudo find storage -type f -exec chmod 664 {} \;
sudo find bootstrap/cache -type f -exec chmod 664 {} \;

# Ensure the web server user group can write
echo "Setting group ownership..."
sudo chgrp -R $WEB_USER storage bootstrap/cache

# Set sticky bit on directories to preserve group ownership
echo "Setting sticky bit on directories..."
sudo find storage -type d -exec chmod g+s {} \;
sudo find bootstrap/cache -type d -exec chmod g+s {} \;

# Fix OAuth key permissions (Laravel Passport)
if [ -f "storage/oauth-private.key" ]; then
    echo "Fixing OAuth private key permissions..."
    sudo chown $WEB_USER:$WEB_USER storage/oauth-private.key
    sudo chmod 600 storage/oauth-private.key
    echo "OAuth private key permissions set to 600"
fi

if [ -f "storage/oauth-public.key" ]; then
    echo "Fixing OAuth public key permissions..."
    sudo chown $WEB_USER:$WEB_USER storage/oauth-public.key
    sudo chmod 600 storage/oauth-public.key
    echo "OAuth public key permissions set to 600"
fi

echo ""
echo "Permissions fixed successfully!"
echo "Storage and cache directories are now writable by the web server."
echo "OAuth keys have been secured with proper permissions."

