# Frezka Admin - Ploi Deployment

## Server
- **IP:** 37.60.233.114
- **SSH:** `ssh ploi@37.60.233.114`
- **Domain:** https://vujoideprodukcija.vujo.software

## 1. Ploi Site Settings (Dashboard)

| Setting | Value |
|---------|-------|
| Web Directory | `public` |
| PHP Version | 8.1 or 8.2 |
| Root Directory | *(empty - repo root is Laravel app)* |

## 2. Ploi Deploy Commands

Paste in Ploi > Site > Deploy Script:

```bash
cd /home/ploi/vujoideprodukcija.vujo.software
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run prod
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

## 3. Git - Push to your repo

```bash
cd admin-panel/frezka_admin_panel_v4.2.0
git remote add origin https://github.com/Leonardo11W/vujoideprodukcija.git
git branch -M main
git push -u origin main
```

**Ploi:** Connect repository `https://github.com/Leonardo11W/vujoideprodukcija` and set branch to `main`.

**Napomena:** Repo je optimiziran za push (bez .map, blog slika, dummy-images). Za demo sadržaj postavi `IS_DUMMY_DATA=true` u .env – slike se mogu dodati ručno u `public/blog/`, `public/dummy-images/` ako treba.

## 4. First-time setup (SSH)

```bash
ssh ploi@37.60.233.114
cd ~/vujoideprodukcija.vujo.software  # or path shown in Ploi
```

### 4a. Create .env
- Ploi: Site > Environment - paste from `.env.production.example`
- Or: `cp .env.production.example .env` and edit
- Fill: `APP_KEY` (run `php artisan key:generate`), `DB_*`, `MAIL_*`

### 4b. Create MySQL database (Ploi Dashboard)
- Databases > Add Database > name: `frezka_prod`
- Add user with full privileges
- Copy credentials to `.env`

### 4c. Migrate (first deploy only)
```bash
php artisan migrate:fresh --seed --force
```

### 4d. Storage link
```bash
php artisan storage:link
# If symlink disabled: ln -s $(pwd)/storage/app/public $(pwd)/public/storage
```

### 4e. Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

## 5. SSL (Ploi Dashboard)
Sites > vujoideprodukcija.vujo.software > SSL > Let's Encrypt > Request

**DNS:** A record for `vujoideprodukcija.vujo.software` → `37.60.233.114`

## Default admin (after seed)
- Email: admin@salon.com
- Password: 12345678
- **Change immediately after first login**
