# Farmer flows on legacy `/partner/*`

## 1) Manual lot creation
- Open `/partner/lots`.
- Submit the lot form.
- System generates `qr_token` and QR assets.
- Publish status depends on `auto_publish_lot`.

## 2) Update/Delete lot
- Update via `PUT /partner/lots/{id}`.
- Delete via `DELETE /partner/lots/{id}`.

## 3) CSV import
- Upload file to `POST /partner/lots/import-csv`.
- Valid rows create lots.
- Invalid rows return line-level errors.

## 4) Regenerate QR
- Call `POST /partner/lots/{id}/regenerate-qr`.
- New token replaces the previous token.

## 5) Dedicated QR page
- Route: `GET /partner/qr`.
- Features: lot QR listing, package creation from lots, package QR regeneration, package update/delete.

## Access rule
- Only role `farmer` can use this workspace.
- Role `partner` does not share the lot dashboard anymore.
