# Laravel Deployment Permissions Guide

## Common Permission Issues on VPS

Laravel needs write permissions on `storage` and `bootstrap/cache` directories. After deployment, these directories may not have the correct permissions, causing errors like:

```
file_put_contents(.../storage/framework/views/...): Failed to open stream: Permission denied
```

## Quick Fix

### Option 1: Use the deployment script (Recommended)
```bash
cd /var/www/AINET2-API
sudo bash deploy.sh
```

### Option 2: Fix permissions manually
```bash
cd /var/www/AINET2-API
sudo bash fix-permissions.sh
```

### Option 3: Quick manual fix
```bash
cd /var/www/AINET2-API
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R 775 bootstrap/cache
```

## Why This Happens

1. **File ownership**: Files uploaded via Git/FTP may be owned by your user, not the web server user
2. **Directory permissions**: New directories may not have write permissions for the web server
3. **Group permissions**: The web server needs group write access

## Web Server User

The default web server user is usually:
- **Apache**: `www-data` or `apache`
- **Nginx**: `www-data` or `nginx`
- **Check your server**: `ps aux | grep -E 'nginx|apache|httpd' | grep -v grep`

If your server uses a different user, edit the scripts and replace `www-data` with your web server user.

## Post-Deployment Checklist

After deploying code, always run:

1. ✅ Install dependencies: `composer install --no-dev --optimize-autoloader`
2. ✅ Run migrations: `php artisan migrate --force`
3. ✅ Clear caches: `php artisan config:clear && php artisan cache:clear && php artisan view:clear`
4. ✅ Cache config: `php artisan config:cache && php artisan route:cache`
5. ✅ Fix permissions: `sudo bash fix-permissions.sh`
6. ✅ Create storage link: `php artisan storage:link`

## Troubleshooting

### Check current permissions:
```bash
ls -la storage/framework/views
ls -la bootstrap/cache
```

### Check web server user:
```bash
ps aux | grep -E 'nginx|apache|httpd' | head -1
```

### Test write permissions:
```bash
sudo -u www-data touch storage/framework/views/test.txt
sudo -u www-data rm storage/framework/views/test.txt
```

If the test fails, permissions are incorrect.

## Automated Deployment

For CI/CD pipelines, add these commands to your deployment script:

```bash
# After code deployment
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

