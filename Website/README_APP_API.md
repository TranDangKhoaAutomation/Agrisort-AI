# AGRISORT-AI

## 1. Website này dùng để làm gì

AGRISORT-AI là hệ thống quản trị và truy xuất nguồn gốc cho nông sản.

Hệ thống có 4 phần chính:

1. Website public
   - Giới thiệu giải pháp
   - Blog
   - Tra cứu truy xuất theo QR/token
   - Tài liệu API

2. Dashboard nội bộ theo vai trò
   - `admin`: quản trị người dùng, điều phối truy xuất, blog, Website Studio, cài đặt hệ thống, API key
   - `partner` / `farmer`: tạo lô hàng, tạo gói hàng, tải QR
   - `transporter` / `warehouse` / `seller`: xem phân công và cập nhật sự kiện hành trình

3. App API cho mobile/app nội bộ
   - đăng nhập bằng Bearer token
   - thao tác theo vai trò
   - tra cứu trace, blog, lot, package, assignment, event

4. Machine API cho máy phân loại
   - nhận dữ liệu từ máy
   - tạo lô hàng tự động bằng `X-API-Key`

## 2. Cách vận hành chính

Luồng cơ bản:

1. Đối tác hoặc nông dân tạo `lô hàng`
2. Hệ thống sinh `qr_token`
3. Đối tác có thể tạo `gói hàng` từ lô
4. Admin phân công actor cho từng chặng truy xuất
5. Vận chuyển / kho / người bán cập nhật sự kiện hành trình
6. Người dùng cuối quét QR hoặc nhập token để xem lịch sử truy xuất

Lưu ý quan trọng:

- QR hiện tại nhúng `token` thuần, không nhúng URL đầy đủ
- Web public tra cứu ở `/trace/{token}`
- App API tra cứu ở `/api/v1/app/trace/lookup?token=...`

## 3. Các nhóm người dùng

### Khách public
- xem trang chủ: `/`
- xem blog: `/blog`
- tra cứu truy xuất: `/trace`
- xem tài liệu: `/docs`
- xem tài liệu App API: `/api-docs/app`

### Admin
- dashboard hub: `/dashboard/admin`
- điều phối truy xuất: `/dashboard/admin/trace`
- quản lý người dùng: `/dashboard/admin/users`
- quản lý blog: `/dashboard/admin/blog`
- Website Studio: `/dashboard/admin/studio`
- cài đặt hệ thống: `/dashboard/admin/settings`
- API key: `/dashboard/admin/api-keys`

### Partner / Farmer
- dashboard vai trò: `/dashboard`
- quản lý QR: `/partner/qr`
- quản lý lô/gói: `/partner/lots`

### Transporter / Warehouse / Seller
- dashboard vai trò: `/dashboard`
- nhận assignment và cập nhật event qua dashboard hoặc App API

## 4. Dùng web như thế nào

### Public

1. Mở `/`
2. Nếu cần tra cứu QR, vào `/trace`
3. Quét QR hoặc nhập token
4. Hệ thống trả về thông tin lô/gói và timeline truy xuất

### Admin

1. Đăng nhập tại `/auth/login`
2. Vào `/dashboard/admin`
3. Từ dashboard hub:
   - vào `Quản lý người dùng` để duyệt/khóa user
   - vào `Điều phối truy xuất` để gán actor cho stage
   - vào `Quản lý blog` để lọc/tìm kiếm/chỉnh bài viết theo trang
   - vào `Website Studio` để sửa đúng section cần thiết của các trang chính
   - vào `Cài đặt hệ thống` để sửa SMTP, QR, dịch tự động
   - vào `API key` để tạo key cho máy

### Partner / Farmer

1. Đăng nhập
2. Vào `/dashboard` hoặc `/partner/lots`
3. Tạo lô hàng
4. Tạo gói hàng nếu cần
5. Tải QR PNG/SVG
6. Dùng token trong QR để tra cứu trên app/web

### Supply actor

1. Đăng nhập đúng vai trò
2. Xem phân công
3. Cập nhật event theo chặng được giao
4. Có thể đính kèm file qua App API

## 5. App API

### Base URL local
- `https://localhost`

### Prefix
- App API: `/api/v1/app`
- Machine API: `/api/v1/machine`

### App API response envelope

```json
{
  "success": true,
  "message": "string",
  "data": {},
  "errors": null,
  "meta": null
}
```

### Auth model
- App dùng `Authorization: Bearer <token>`
- Token được cấp từ:
  - `POST /api/v1/app/auth/register`
  - `POST /api/v1/app/auth/login`

### App API theo nhóm

#### Auth
- `POST /api/v1/app/auth/register`
- `POST /api/v1/app/auth/login`
- `POST /api/v1/app/auth/logout`
- `GET /api/v1/app/auth/me`
- `PUT /api/v1/app/auth/profile`
- `PUT /api/v1/app/auth/password`
- `POST /api/v1/app/auth/forgot-password`
- `POST /api/v1/app/auth/reset-password`

#### Public
- `GET /api/v1/app/blog`
- `GET /api/v1/app/blog/{slug}`
- `GET /api/v1/app/trace/lookup?token=...`
- `GET /api/v1/app/trace/{qr_token}`

#### Partner / Farmer
- `GET /api/v1/app/partner/lots`
- `POST /api/v1/app/partner/lots`
- `GET /api/v1/app/partner/lots/{id}`
- `PUT /api/v1/app/partner/lots/{id}`
- `DELETE /api/v1/app/partner/lots/{id}`
- `POST /api/v1/app/partner/lots/{id}/regenerate-qr`
- `GET /api/v1/app/partner/packages`
- `GET /api/v1/app/partner/packages/{id}`
- `POST /api/v1/app/partner/packages`
- `PUT /api/v1/app/partner/packages/{id}`
- `DELETE /api/v1/app/partner/packages/{id}`
- `POST /api/v1/app/partner/packages/{id}/regenerate-qr`

#### Supply actor
- `GET /api/v1/app/supply/assignments`
- `GET /api/v1/app/supply/events`
- `POST /api/v1/app/supply/events`
- `PUT /api/v1/app/supply/events/{id}`

#### Admin
- `GET /api/v1/app/admin/dashboard`
- `GET /api/v1/app/admin/users`
- `POST /api/v1/app/admin/users/{id}/status`
- `POST /api/v1/app/admin/users/{id}/vip` (giữ để tương thích kỹ thuật cho mốc truy cập mở rộng)
- `POST /api/v1/app/admin/partners/{id}/approve`
- `GET /api/v1/app/admin/settings`
- `POST /api/v1/app/admin/settings`
- `GET /api/v1/app/admin/cms/{section_key}`
- `POST /api/v1/app/admin/cms/{section_key}`
- `GET /api/v1/app/admin/blog`
- `POST /api/v1/app/admin/blog`
- `GET /api/v1/app/admin/api-keys`
- `POST /api/v1/app/admin/api-keys`
- `POST /api/v1/app/admin/lots/{id}/publish`
- `GET /api/v1/app/admin/trace/assignments`
- `POST /api/v1/app/admin/trace/assignments`
- `DELETE /api/v1/app/admin/trace/assignments/{id}`
- `GET /api/v1/app/admin/trace/events`

### Tài liệu API sẵn có trên web
- App API portal: `/api-docs/app`
- App OpenAPI JSON: `/api-docs/app/openapi.json`
- App Postman JSON: `/api-docs/app/postman.json`
- Web/OpenAPI portal chung: `/docs`
- OpenAPI JSON chung: `/docs/openapi.json`
- Postman JSON chung: `/docs/postman.json`

## 6. Machine API

### Endpoint
- `POST /api/v1/machine/lots`

### Auth
- Header: `X-API-Key: <api_key>`

### Dùng để làm gì
- Máy phân loại gửi kết quả phân loại về hệ thống
- Hệ thống tạo lô mới, sinh token QR và trạng thái publish

### Payload chính
- `partner_ref`
- `lot_code`
- `produce_type`
- `origin_region`
- `harvest_date`
- `grade1_count`
- `grade2_count`
- `defect_count`
- `image_url`
- `notes` là tùy chọn

## 7. Tài khoản demo local

Tất cả dùng chung mật khẩu:

```text
Admin@123456
```

Tài khoản:

- `agrisort.demo.admin@gmail.com`
- `agrisort.demo.partner@gmail.com`
- `agrisort.demo.farmer@gmail.com`
- `agrisort.demo.transport@gmail.com`
- `agrisort.demo.warehouse@gmail.com`
- `agrisort.demo.seller@gmail.com`

## 8. Trạng thái kiểm tra hiện tại

Ngày kiểm tra: **2026-03-10**

### Đã kiểm tra pass

#### Test code
- `php tests/run.php`
- pass toàn bộ:
  - docs consistency
  - encoding consistency
  - slug/validator
  - QR token payload
  - trace token extract

#### Smoke test web public
- `GET /` => `200`
- `GET /blog` => `200`
- `GET /trace` => `200`
- `GET /docs` => `200`
- `GET /api-docs/app` => `200`
- `GET /api-docs/app/openapi.json` => `200`
- `GET /api-docs/app/postman.json` => `200`
- `GET /docs/openapi.json` => `200`
- `GET /docs/postman.json` => `200`

#### Smoke test web sau đăng nhập

Admin session:
- `/dashboard/admin` => `200`
- `/dashboard/admin/blog` => `200`
- `/dashboard/admin/studio?section=hero` => `200`
- `/dashboard/admin/settings` => `200`
- `/dashboard/admin/api-keys` => `200`
- `/dashboard/admin/trace` => `200`
- `/dashboard/admin/users` => `200`

Partner session:
- `/dashboard` => `200`
- `/partner/qr` => `200`
- `/partner/lots` => `200`

Seller session:
- `/dashboard` => `200`

#### Smoke test App API

Public:
- blog list ok
- trace lookup ok với token `agrisort-lot-001`

Partner token:
- login ok
- list lots ok
- list packages ok

Seller token:
- login ok
- list assignments ok
- list events ok

Admin token:
- login ok
- dashboard ok
- users ok
- settings ok
- cms section ok
- blog list ok
- api key list ok
- trace assignments ok
- trace events ok

Machine API:
- tạo API key test thành công
- gọi `POST /api/v1/machine/lots` thành công
- tạo được `lot_id=17`

## 9. Lỗi đã phát hiện và đã sửa

Trong lúc kiểm tra App API, đăng nhập app từng bị lỗi do thiếu bảng:

- `user_api_tokens`

Đã sửa theo hướng tự đồng bộ schema:

- thêm `SchemaSyncService::ensureUserApiTokensTable()`
- gọi tự động trong `UserApiToken`

Kết quả sau sửa:
- app login hoạt động lại
- token Bearer dùng được cho các nhóm endpoint app

## 10. Những phần chưa kiểm sâu

Các mục dưới đây chưa được click tay toàn bộ từng biến thể:

- luồng upload file lớn cho supply event attachments
- luồng forgot/reset password phụ thuộc SMTP thực tế
- mọi thao tác create/update/delete của tất cả role trên web UI
- toàn bộ nhánh lỗi 4xx/5xx của App API

Nói ngắn gọn:

- hệ thống đang **ổn ở mức test + smoke test local**
- web public, dashboard chính, App API chính và Machine API chính đều đã lên được
- phần cần kiểm sâu tiếp theo nếu muốn là `upload`, `email`, và `toàn bộ mutation flow`
