# Triển khai

## Checklist hosting panel
1. Upload source code.
2. Chạy `composer install --no-dev`.
3. Tạo `.env` production.
4. Import `database.sql`.
5. Trỏ domain/subdomain đúng vào web root.
6. Bật HTTPS và redirect HTTP -> HTTPS.

## Cấu hình quan trọng
- `APP_URL=https://your-domain.com`
- `DB_*` production
- `SMTP_*` (Gmail App Password hoặc SMTP riêng)
- `GOOGLE_TRANSLATE_API_KEY`

## QR base URL
Trong admin settings, cấu hình `qr_base_url` là domain public để mã QR quét được từ điện thoại ngoài mạng local.

## Hậu kiểm
- Mở `/docs`, `/docs/openapi.json`, `/docs/postman.json`.
- Test login admin/partner.
- Test tạo lô và quét QR ngoài thiết bị mobile.
