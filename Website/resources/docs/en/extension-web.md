# Web extension

## Add new feature workflow
1. Add route in `routes/web.php`.
2. Implement controller action.
3. Add model/service logic as needed.
4. Create/update view in `resources/views`.
5. Add i18n keys in both `resources/lang/vi.php` and `resources/lang/en.php`.
6. Update `resources/docs/route_catalog.php`.
7. Update relevant markdown docs.
8. Run docs consistency + smoke tests.

## Backward compatibility
- Do not break existing route contracts.
- If contract changes are required, create a new versioned endpoint.
- Keep fallback behavior for existing clients.

## Styling rules
- Reuse design tokens in `public/css/app.css`.
- Avoid inline style in views.
- Validate mobile responsiveness before merge.
