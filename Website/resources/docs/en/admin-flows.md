# Admin flows

## Dashboard KPIs
- Pending partners.
- Total lots.
- New leads (contact/consultation).
- Blog post count.

## Primary operations
- Approve/block partner accounts.
- Manage settings (`auto_publish_lot`, SMTP, QR base URL, translate key).
- Manage landing CMS content (VI/EN + auto translate).
- Create/update/publish blog posts.
- Create machine API keys.
- Toggle lot publish state.

## Auditability
Sensitive actions should be recorded in `audit_logs` for traceability.

## Trace admin flow update (2026-02-27)

### New routes
- `GET /dashboard/admin/trace`
- `POST /dashboard/admin/users/{id}/status`
- `POST /dashboard/admin/trace/assignments`
- `DELETE /dashboard/admin/trace/assignments/{id}`

### Capabilities
- Approve pending actors: farmer/transporter/warehouse/seller.
- Assign stages to `lot` or `package` entities.
- Monitor system-wide timeline and event attachments.
