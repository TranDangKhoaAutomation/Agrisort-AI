# Danh mục web routes

Toàn bộ route chi tiết (method, auth, role, csrf, schema request/response) được quản lý tại:

`resources/docs/route_catalog.php`

## Nhóm route
- Public: landing/blog/trace/docs/contact/tư vấn.
- Auth: login/register/forgot/reset/logout/account.
- Partner: dashboard + CRUD lot + import CSV + regenerate QR.
- Admin: dashboard + approve partner + settings + CMS + blog + api-keys.
- Machine API: `POST /api/v1/machine/lots`.

## Route tài khoản mới
- `GET /account`: trang tài khoản cho user đã đăng nhập.
- `POST /account/profile`: cập nhật profile (admin + partner, partner có thêm profile doanh nghiệp).
- `POST /account/password`: đổi mật khẩu với yêu cầu nhập mật khẩu hiện tại.

## Quy tắc đồng bộ
- Không thêm route mới mà quên cập nhật catalog.
- Chạy test consistency:
```bash
php tests/docs_consistency.php
```
- Route matrix trên `/docs` cũng lấy từ catalog này.
