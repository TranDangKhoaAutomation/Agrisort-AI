# Security

## Implemented controls
- CSRF token for POST forms (except routes explicitly marked `csrf=false`).
- Output escaping via `e()` to reduce XSS risk.
- Input validation at controller/service level.
- File upload validation for type/size.
- Rate limiting for login, forgot-password, machine API.
- Expiring one-time password reset tokens.
- API keys stored as hash values.
- Audit logging for sensitive operations.

## Production recommendations
- Enforce HTTPS.
- Rotate default credentials immediately.
- Add request limits at web server/proxy level.
- Monitor `storage/logs` continuously.
