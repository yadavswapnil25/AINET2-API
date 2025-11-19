#!/bin/bash

# Fix Laravel Storage and Cache Permissions
# Run this script on your VPS server

# Navigate to your Laravel project directory
cd /var/www/AINET2-API

# Set ownership to www-data (or your web server user)
# Replace www-data with your web server user if different (nginx, apache, etc.)
WEB_USER="www-data"
sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache

# Set directory permissions (755 = rwxr-xr-x)
sudo find storage -type d -exec chmod 755 {} \;

# Set file permissions (644 = rw-r--r--)
sudo find storage -type f -exec chmod 644 {} \;

# Set directory permissions for bootstrap/cache
sudo find bootstrap/cache -type d -exec chmod 755 {} \;

# Set file permissions for bootstrap/cache
sudo find bootstrap/cache -type f -exec chmod 644 {} \;

# Make storage and cache directories writable
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache

# Ensure the web server user can write to these directories
sudo chgrp -R $WEB_USER storage bootstrap/cache

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
    sudo chmod 644 storage/oauth-public.key
    echo "OAuth public key permissions set to 644"
fi

echo ""
echo "Permissions fixed successfully!"
echo "Storage and cache directories are now writable by the web server."
echo "OAuth keys have been secured with proper permissions."

