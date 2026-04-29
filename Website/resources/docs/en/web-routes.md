# Web routes catalog

The complete route contract (method, auth, role, csrf, request, response, errors) is maintained in:

`resources/docs/route_catalog.php`

## Route groups
- Public: landing/blog/trace/docs/contact/consultation.
- Auth: login/register/forgot/reset/logout/account.
- Partner: dashboard + lot CRUD + CSV import + QR regenerate.
- Admin: dashboard + partner approval + settings + CMS + blog + API keys.
- Machine API: `POST /api/v1/machine/lots`.

## New account routes
- `GET /account`: account page for authenticated users.
- `POST /account/profile`: update profile (admin + partner; partner includes organization profile fields).
- `POST /account/password`: change password with current password verification.

## Sync rule
Do not add routes without updating the catalog.

```bash
php tests/docs_consistency.php
```

The route matrix rendered at `/docs` is generated from the same catalog.
