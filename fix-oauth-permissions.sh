#!/bin/bash

# Quick fix for OAuth key permissions
# Run this script on your VPS server

cd /var/www/AINET2-API

# Replace www-data with your web server user if different
WEB_USER="www-data"

echo "Fixing OAuth key permissions..."

# Fix OAuth private key (should be 600 - only owner can read/write)
if [ -f "storage/oauth-private.key" ]; then
    sudo chown $WEB_USER:$WEB_USER storage/oauth-private.key
    sudo chmod 600 storage/oauth-private.key
    echo "✓ OAuth private key permissions set to 600"
else
    echo "⚠ OAuth private key not found. Run: php artisan passport:keys"
fi

# Fix OAuth public key (can be 644 - readable by all)
if [ -f "storage/oauth-public.key" ]; then
    sudo chown $WEB_USER:$WEB_USER storage/oauth-public.key
    sudo chmod 644 storage/oauth-public.key
    echo "✓ OAuth public key permissions set to 644"
else
    echo "⚠ OAuth public key not found. Run: php artisan passport:keys"
fi

echo ""
echo "OAuth key permissions fixed!"

