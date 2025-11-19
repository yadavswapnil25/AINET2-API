#!/bin/bash

# Fix Laravel Storage and Cache Permissions
# Run this script on your VPS server

# Navigate to your Laravel project directory
cd /var/www/AINET2-API

# Set ownership to www-data (or your web server user)
# Replace www-data with your web server user if different (nginx, apache, etc.)
sudo chown -R www-data:www-data storage bootstrap/cache

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
sudo chgrp -R www-data storage bootstrap/cache

echo "Permissions fixed successfully!"
echo "Storage and cache directories are now writable by the web server."

