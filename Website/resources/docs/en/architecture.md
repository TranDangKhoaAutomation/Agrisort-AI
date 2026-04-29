# Architecture

## Request flow
1. Request enters `index.php`.
2. `Request::capture()` normalizes method/path.
3. Router resolves route definitions in `routes/web.php` and `routes/api.php`.
4. Router-level guard options apply (`auth`, `role`, `csrf`).
5. Controller uses model/service and renders HTML or JSON.

## Main layers
- `app/Core`: request lifecycle and security primitives.
- `app/Controllers`: public/auth/partner/admin/api/docs endpoints.
- `app/Models`: PDO-based data access.
- `app/Services`: CSV, QR, mail, translate, audit, docs.
- `resources/views`: PHP templates.

## System technical profile (from project dossier)
- Pipeline: Camera -> Computer Vision -> AI Model -> Grading Decision -> Sorting Actuator.
- Pilot accuracy: `93-96%`.
- Target throughput: `1,000-2,000 fruits/hour`.
- Target decision latency: `<= 200 ms`.
- Primary deployment scope: cooperatives, post-harvest stations, SME agribusinesses.

## Public implementation team
- Nguyen Khac Tung Lam - Team Lead / System Integration.
- Nguyen Dang Quang - AI & Data Pipeline.
- Vo Thi My - Business & Market Analysis.
- Tran Dang Khoa - Embedded & Control.
- Le Huu Trang - Mechanical & Automation.
- Vu Van Dung, B.Eng. - Advisor.
- Nguyen Thi Thanh, M.A. - Advisor.

## Docs architecture
- Markdown files in `resources/docs/{vi|en}`.
- `manifest.php` controls ordering and titles.
- `route_catalog.php` defines route contracts.
- DocsService generates OpenAPI/Postman at runtime.
