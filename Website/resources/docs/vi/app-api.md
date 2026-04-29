# API ứng dụng cho Android Studio

## Tổng quan
- Base namespace: `/api/v1/app/*`
- Xác thực: `Authorization: Bearer <access_token>`
- Phản hồi chuẩn:

```json
{
  "success": true,
  "message": "Localized message",
  "data": {},
  "errors": null,
  "meta": null
}
```

## Phiên đăng nhập
- Access token mặc định có hiệu lực `30 ngày`.
- Chưa có refresh token ở phiên bản hiện tại.
- Token hiện tại bị thu hồi khi gọi `POST /api/v1/app/auth/logout`.

## Nhóm endpoint
- `auth`: đăng ký, đăng nhập, đăng xuất, thông tin tài khoản, hồ sơ, mật khẩu, quên mật khẩu, đặt lại mật khẩu.
- `public`: blog, tra cứu truy xuất theo token, xem chi tiết truy xuất.
- `partner/farmer`: CRUD lô, CRUD gói, tái tạo QR cho lô và gói.
- `supply`: danh sách assignment và CRUD sự kiện vận hành theo chặng.
- `admin`: dashboard, người dùng, duyệt đối tác, settings, CMS, blog, API key, trace assignments và trace events.

## Truy xuất công khai qua App API
- `GET /api/v1/app/trace/lookup?token=...`
- `GET /api/v1/app/trace/{qr_token}`
- Mặc định có thể dùng công khai; nếu hệ thống bật cờ hạn chế thì cần tuân theo chính sách đăng nhập hoặc truy cập mở rộng.

## Ghi chú tích hợp
- Ảnh lô trên app/web dùng `image_url` hoặc `thumbnail_url` trong payload trả về.
- Payload QR hiện dùng token truy xuất thuần, còn app sẽ tự mở trang trace tương ứng.
- Route `POST /api/v1/app/admin/users/{id}/vip` được giữ để tương thích kỹ thuật cho mốc truy cập mở rộng nội bộ.
