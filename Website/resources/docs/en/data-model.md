# Data model

Schema file: `database.sql`.

## Core tables
- `users`
- `partner_profiles`
- `lots`
- `lot_import_jobs`
- `demo_requests`
- `contact_messages`
- `blog_posts`
- `cms_sections`
- `admin_settings`
- `password_resets`
- `api_keys`
- `translation_cache`
- `audit_logs`

## Key relations
- `users (id)` 1-n `partner_profiles (user_id)`.
- `users (id)` 1-n `lots (partner_user_id)`.
- `users (id)` 1-n `audit_logs (actor_user_id)`.

## Suggested indexes
- unique `users.email`
- unique `lots.qr_token`
- unique `blog_posts.slug`
- unique/indexed `password_resets.token`
- indexed `api_keys.key_hash`

## Trace-supply data model update (2026-02-27)

### users.role
- Added roles: `farmer`, `transporter`, `warehouse`, `seller`.
- Keeps `partner` for legacy compatibility.

### lot_packages
- Package records linked to lots (`lot_id`) with `package_code`, `package_label`, `quantity`, `net_weight_kg`, `qr_token`, and `publish_status`.

### trace_assignments
- Controls which actor can update which stage on `lot`/`package`.

### trace_events
- Transport timeline events with `stage_code`, `event_time`, `location_name`, `note`, and `actor_user_id`.

### trace_event_attachments
- Event document storage (image/pdf) linked by `event_id`.
