# Local setup

## Prerequisites
- XAMPP (Apache + MySQL) with PHP 8.1.
- Composer.
- Browser testing on desktop/mobile.

## Quick start
1. Copy source into `d:\xampp\htdocs` or `d:\YTKN\website`.
2. Install dependencies:
```bash
composer install
```
3. Create environment file:
```bash
copy .env.example .env
```
4. Configure `.env`: `APP_URL`, `DB_*`, `SMTP_*`, `GOOGLE_TRANSLATE_API_KEY`.
5. Create DB and import schema:
```bash
mysql -u root -p < database.sql
```
6. Open `http://localhost/`.

## Default admin
- Email: `admin@gmail.com`
- Password: `Admin@123456`

## Primary URLs
- `/`
- `/auth/login`
- `/dashboard` (role-aware dashboard entrypoint)
- `/dashboard/admin` (admin dashboard)
- `/docs`
