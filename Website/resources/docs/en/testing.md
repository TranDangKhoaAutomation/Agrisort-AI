# Testing

## Scripts
```bash
php tests/run.php
php tests/docs_consistency.php
```

## Required smoke scenarios
1. New partner cannot access dashboard before approval.
2. Admin approves partner; partner creates lot.
3. CSV import reports row-level errors.
4. `auto_publish_lot` ON/OFF changes lot visibility.
5. `/trace/{qr_token}` returns correct trace payload.
6. Contact/consultation forms persist and appear in admin.
7. Forgot/reset password flow works and token expires correctly.
8. CMS VI update + EN auto-translate renders correctly.
9. Blog publish/edit/slug access works.
10. Machine API accepts valid key and rejects invalid keys.
11. Unauthorized role access returns 403.
12. Docs route consistency passes.

## Regression checklist
- Desktop/mobile responsive check.
- Docs code-copy button works.
- Docs nav visible for guest and authenticated users.
