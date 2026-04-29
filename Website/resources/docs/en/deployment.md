# Deployment

## Hosting panel checklist
1. Upload source code.
2. Run `composer install --no-dev`.
3. Create production `.env`.
4. Import `database.sql`.
5. Point domain/subdomain to correct web root.
6. Enable HTTPS and HTTP -> HTTPS redirect.

## Required configuration
- `APP_URL=https://your-domain.com`
- Production `DB_*`
- `SMTP_*`
- `GOOGLE_TRANSLATE_API_KEY`

## QR base URL
Set `qr_base_url` in admin settings to a public domain so phone scanners can open trace links.

## Post-deploy checks
- Verify `/docs`, `/docs/openapi.json`, `/docs/postman.json`.
- Verify admin/partner login flows.
- Verify lot creation and QR scan on mobile.
