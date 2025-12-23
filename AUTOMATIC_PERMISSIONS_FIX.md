# Automatic Permission Fix Solution

## Problem
After each deployment, Laravel storage directories lose write permissions, causing errors like:
```
file_put_contents(/var/www/AINET2-API/storage/framework/views/...): Permission denied
```

## Why This Happens
- Files uploaded via Git/FTP are owned by your user, not the web server user (`www-data`)
- New directories may not have write permissions
- The web server needs group write access to function

## Automatic Solutions Implemented

### 1. **Laravel Artisan Command** (Check/Fix Permissions)
```bash
# Check permissions
php artisan storage:fix-permissions --check

# Fix permissions (if possible without sudo)
php artisan storage:fix-permissions
```

### 2. **AppServiceProvider** (Auto-creates directories)
The `AppServiceProvider` now automatically creates all required storage directories when the app boots, ensuring they always exist.

### 3. **Composer Post-Update Hook**
After `composer install/update`, permissions are automatically checked.

### 4. **Deployment Script** (`deploy.sh`)
The deployment script now:
- Creates all required directories
- Sets proper ownership and permissions
- Verifies permissions after fixing

## Usage

### Option 1: Use Deployment Script (Recommended)
```bash
cd /var/www/AINET2-API
sudo bash deploy.sh
```

### Option 2: Manual Fix (One-time)
```bash
cd /var/www/AINET2-API
sudo bash fix-permissions.sh
```

### Option 3: Check Only (No sudo required)
```bash
php artisan storage:fix-permissions --check
```

## For CI/CD (GitHub Actions)
If you use GitHub Actions, the `.github/workflows/deploy.yml` file will automatically run permission fixes after deployment.

## Permanent Solution

To make this truly automatic, add this to your server's crontab or deployment hook:

```bash
# Add to crontab (runs after every deployment)
@reboot cd /var/www/AINET2-API && sudo bash fix-permissions.sh
```

Or add to your Git post-receive hook:
```bash
#!/bin/bash
cd /var/www/AINET2-API
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo bash fix-permissions.sh
```

## Troubleshooting

If permissions still fail:
1. Check web server user: `ps aux | grep -E 'apache|nginx|php-fpm'`
2. Verify ownership: `ls -la storage/framework/views`
3. Check group: `groups www-data` (or your web server user)
4. Run manual fix: `sudo bash fix-permissions.sh`

## What Gets Fixed

- `storage/app/public` - Public file storage
- `storage/framework/cache` - Application cache
- `storage/framework/sessions` - Session files
- `storage/framework/testing` - Test files
- `storage/framework/views` - Compiled Blade views
- `storage/logs` - Application logs
- `bootstrap/cache` - Bootstrap cache

All directories are set to `775` (rwxrwxr-x) and files to `664` (rw-rw-r--).

