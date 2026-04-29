# AGRISORT-AI Website (PHP + MySQL)

## Run local with XAMPP
1. Copy `.env.example` to `.env` and fill DB/SMTP values.
2. Create database with `database.sql`.
3. Install dependencies:
   - `composer install`
4. Open: `http://localhost/`

## Default admin
- Email: `admin@gmail.com`
- Password: `Admin@123456`

## Implemented modules
- Public landing VI/EN + contact + consultation form
- Blog public
- QR trace public (`/trace/{qr_token}`)
- Developer Docs portal (`/docs`) with:
  - bilingual content (VI/EN)
  - route matrix
  - OpenAPI export (`/docs/openapi.json`)
  - Postman collection (`/docs/postman.json`)
- App API docs portal (`/api-docs/app`) with:
  - app-only route matrix (`/api/v1/app/*`)
  - OpenAPI export (`/api-docs/app/openapi.json`)
  - Postman collection (`/api-docs/app/postman.json`)
- Partner register/login, pending approval flow
- Partner lot CRUD + CSV import + QR regenerate
- Admin dashboard:
  - Partner approval
  - Settings (`auto_publish_lot`, smtp host/port/user/pass/from, translate key)
  - CMS sections basic
  - Blog CMS basic
  - API key management
  - Lot publish status control
  - Lead management
- Forgot/reset password (SMTP via PHPMailer)
- Machine API endpoint `POST /api/v1/machine/lots`
- App API namespace `/api/v1/app/*` with Bearer token auth (multi-role)

## Developer docs sources
- Docs manifest: `resources/docs/manifest.php`
- Route catalog source of truth: `resources/docs/route_catalog.php`
- Markdown sections:
  - `resources/docs/vi/*.md`
  - `resources/docs/en/*.md`
- Docs consistency test: `tests/docs_consistency.php`

## Test commands
- `php tests/run.php`
- `php tests/docs_consistency.php`
