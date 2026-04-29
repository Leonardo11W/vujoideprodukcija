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

## 2. Deploy s lokalnog računara (rsync + SSH) — uobičajeni tok nakon izmjena u kodu

Iz repo korijena (prilagođen put do admin panela):

```bash
cd admin-panel/frezka_admin_panel_v4.2.0
# (opciono) svježi frontend asseti
cd Modules/Frontend && npm run prod && cd ../..
# sinkronizacija na server (ne šalje .env, vendor, node_modules)
bash rsync-deploy.sh
# keš i migracije na serveru
ssh ploi@37.60.233.114 'cd ~/vujoideprodukcija.vujo.software && \
  rm -f bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null; \
  php artisan view:clear && \
  php artisan config:cache && \
  php artisan migrate --force && \
  chmod -R 775 storage bootstrap/cache 2>/dev/null'
```

**`php artisan route:cache`:** na ovom projektu često **ne uspije** zbog duplog imena ruta (npr. u prošlosti `save-bank` u `web` + `api`). Aplikacija normalno radi **bez** `route:cache` (rute se učitavaju dinamički). Ako `route:cache` padne, na produkciji pokrenite `php artisan route:clear` da nema oštećenog keša. Duplikate treba ukloniti u kodu prije oslanjanja na keširane rute.

---

## 3. Ploi Deploy Commands (git pull na serveru)

Paste in Ploi > Site > Deploy Script:

```bash
cd /home/ploi/vujoideprodukcija.vujo.software
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run prod
php artisan migrate --force
php artisan config:cache
# php artisan route:cache  # samo ako nema duplog imena ruta; inače: php artisan route:clear
php artisan view:cache
php artisan storage:link
```

## 4. Git - Push to your repo

```bash
cd admin-panel/frezka_admin_panel_v4.2.0
git remote add origin https://github.com/Leonardo11W/vujoideprodukcija.git
git branch -M main
git push -u origin main
```

**Ploi:** Connect repository `https://github.com/Leonardo11W/vujoideprodukcija` and set branch to `main`.

**Napomena:** Repo je optimiziran za push (bez .map, blog slika, dummy-images). Za demo sadržaj postavi `IS_DUMMY_DATA=true` u .env – slike se mogu dodati ručno u `public/blog/`, `public/dummy-images/` ako treba.

## 5. First-time setup (SSH)

```bash
ssh ploi@37.60.233.114
cd ~/vujoideprodukcija.vujo.software  # or path shown in Ploi
```

### 5a. Create .env
- Ploi: Site > Environment - paste from `.env.production.example`
- Or: `cp .env.production.example .env` and edit
- Fill: `APP_KEY` (run `php artisan key:generate`), `DB_*`, `MAIL_*`

### 5b. Create MySQL database (Ploi Dashboard)
- Databases > Add Database > name: `frezka_prod`
- Add user with full privileges
- Copy credentials to `.env`

### 5c. Migrate (first deploy only)
```bash
php artisan migrate:fresh --seed --force
```

### 5d. Storage link
```bash
php artisan storage:link
# If symlink disabled: ln -s $(pwd)/storage/app/public $(pwd)/public/storage
```

### 5e. Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

## 6. SSL (Ploi Dashboard)
Sites > vujoideprodukcija.vujo.software > SSL > Let's Encrypt > Request

**DNS:** A record for `vujoideprodukcija.vujo.software` → `37.60.233.114`

## Default admin (after seed)
- Email: admin@salon.com
- Password: 12345678
- **Change immediately after first login**

## Javni sajt: jezik (bs) i sadržaj iz baze
- U `.env` postavite `APP_LOCALE=bs` i `APP_FALLBACK_LOCALE=bs`, zatim na serveru `php artisan config:cache` i `php artisan view:clear` nakon deploya.
- Tekstovi poput **FAQ pitanja/odgovora**, **Why choose** blokova, SEO i opisnih polja u CMS-u dolaze **iz baze** (uređuje se u adminu), a ne iz `lang/bs/*.php`. Za potpuno bosansko javno lice te sadržaje treba urediti u administraciji (ili, uz backup, jednokratno SQL-om) — ovo nije uključeno u običan deploy prijevoda.

## Mobilna aplikacija: push predlošci i rezervacije
- Migracija `database/migrations/2026_04_26_120000_notification_templates_bs_strings.php` ažurira bosanske tekstove u tablici `notification_template_content_mapping`. Na serveru pokrenite `php artisan migrate --force` nakon deploya koda (predviđeno je već u SSH bloku u §2).
- Kupac u aplikaciji dobiva push/database obavještenja za rezervacije **samo** kad je rezervacija **otkazana** (`cancel_booking`); ostali booking događaji ostaju za admin/manager.

### Ručni QA (nakon migracije)
1. Mobilni korisnik kreira rezervaciju: ne smije doći push ni novi zapis u listi obavještenja za samu potvrdu termina.
2. Korisnik otkaže rezervaciju (`cancelled`): mora doći obavještenje korisniku, tekst na bosanskom.
3. Nova rezervacija: admin ili manager i dalje dobiju obavještenje (nova rezervacija).
