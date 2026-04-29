# RBAC and auth

## Role matrix
- `visitor`: public routes only, including landing, blog, docs, trace lookup, and QR scanning.
- `admin`: uses `/dashboard/admin/*` for users, CMS, traceability, settings, and API keys.
- `partner`: uses `/partner/*` for lot, package, QR, and traceability management.
- `farmer`: shares the `/partner/*` workspace with a smaller producer-oriented operational scope.
- `transporter`: updates transport stages inside the supply workspace.
- `warehouse`: updates warehouse stages inside the supply workspace.
- `seller`: updates downstream selling stages inside the supply workspace.
- `api-key`: machine integration routes via `X-API-Key`.

## Approval flow
- Newly registered accounts default to `pending`.
- Pending users cannot enter operational dashboards.
- Admin activates them from user management.

## Public traceability behavior
- By default, QR traceability is public so buyers or partners can scan published records directly.
- If `TRACE_REQUIRE_LOGIN=true`, guests must sign in before opening trace data.
- If `TRACE_REQUIRE_VIP=true`, the system switches to an extended-access requirement for trace data.
- The `/billing` route is retained for compatibility, but its content now serves deployment support rather than a pricing page.

## Account page after sign-in
- `GET /account` shows the profile and current access status.
- `POST /account/profile` updates account profile data.
- `POST /account/password` changes the password after a valid `current_password`.
