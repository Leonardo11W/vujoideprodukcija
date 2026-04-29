# QA Test Checklist - Admin Panel UI/UX Redesign

**URL:** https://vujoideprodukcija.vujo.software  
**Admin login:** admin@salon.com / 12345678 (promijeni nakon prvog logina)

## Test Lista

| # | Test | Očekivani rezultat | Status |
|---|------|-------------------|--------|
| 1 | **Login** | Uspješan login s admin kredencijalima | |
| 2 | **Dashboard** | Učitava se, kartice prikazuju brojeve, grafovi se renderiraju | |
| 3 | **Date filter** | Odabir date range i Submit mijenja podatke na dashboardu | |
| 4 | **Sidebar** | Svi linkovi rade, aktivna stavka je označena, hover efekt | |
| 5 | **Branch selector** | Admin može promijeniti branch, Apply radi | |
| 6 | **Theme toggle** | Light/Dark prebacuje se ispravno | |
| 7 | **Bookings** | Lista rezervacija se učitava, dodavanje novog bookinga radi | |
| 8 | **Calendar view** | Kalendar se prikazuje i radi | |
| 9 | **Services** | Lista usluga, dodavanje/uređivanje | |
| 10 | **Customers** | Lista kupaca, CRUD | |
| 11 | **Employees** | Lista zaposlenika | |
| 12 | **Orders** | Lista narudžbi | |
| 13 | **Settings** | Settings stranica (Vue) se učitava | |
| 14 | **Profile** | My Profile se učitava i sprema | |
| 15 | **Notifications** | Notifikacije se prikazuju u dropdown-u | |
| 16 | **Responsive** | Mobilni prikaz (sidebar collapse, header) | |
| 17 | **Sub-header** | Na stranicama s bannerom (npr. Bookings) - manji banner, CTA gumbi | |

## UI/UX Promjene za provjeru

- [ ] Kartice imaju zaobljene rubove (0.75rem) i blagu sjenku
- [ ] Dashboard kartice imaju hover efekt (lagani lift)
- [ ] Sidebar aktivna stavka ima pozadinsku boju
- [ ] Sub-header (banner) je niži (~80-100px), tipografija jasnija
- [ ] Footer je minimalniji
- [ ] Dark mode radi ispravno
- [ ] Custom scrollbar u sidebaru i upcoming appointments

## Napomene

- `php artisan route:cache` može failati zbog duplikata imena rute (`save-bank`) - to je postojeći bug u projektu
- Ako `npm run prod` nije završio na serveru, pokreni ručno: `ssh ploi@37.60.233.114` → `cd ~/vujoideprodukcija.vujo.software` → `npm run prod`
