#!/bin/bash
# Frezka Admin Panel - Ploi Deploy Script
# Run this on server after git pull (or use as Ploi Deploy Commands)

set -e

echo "=== Frezka Deploy ==="

# Clear bootstrap cache (fixes BreezeServiceProvider error when Breeze is require-dev)
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# Composer
composer install --no-dev --optimize-autoloader --no-interaction

# NPM
npm ci --production=false
npm run prod

# Laravel
php artisan migrate --force
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Storage link (ignore error if exists)
php artisan storage:link 2>/dev/null || true

# Permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "=== Deploy complete ==="
