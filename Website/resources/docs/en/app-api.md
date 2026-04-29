# App API for Android Studio

## Overview
- Base namespace: `/api/v1/app/*`
- Auth: `Authorization: Bearer <access_token>`
- Response format:

```json
{
  "success": true,
  "message": "Localized message",
  "data": {},
  "errors": null,
  "meta": null
}
```

## Token policy
- Access token lifetime: 30 days.
- No refresh token in this version.
- Current token is revoked on `POST /api/v1/app/auth/logout`.

## Endpoint groups
- Auth: register/login/logout/me/profile/password/forgot/reset.
- Public: blog list/detail, trace lookup/detail.
- Partner/Farmer: lots + packages CRUD and QR regeneration.
- Supply actors: assignments + trace events.
- Admin: dashboard/users/settings/CMS/blog/API keys/trace management.

## Upload support
- Lot image: `multipart/form-data`, field `image`.
- Trace attachments: `multipart/form-data`, field `attachments[]`.
- Allowed attachment types: JPG/PNG/WEBP/PDF up to 10MB each.

