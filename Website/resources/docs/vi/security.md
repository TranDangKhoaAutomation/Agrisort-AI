# Bảo mật

## Biện pháp đã áp dụng
- CSRF token cho mọi form POST (trừ endpoint được đánh dấu `csrf=false`).
- Escape output chống XSS (`e()`).
- Validate dữ liệu đầu vào ở controller/service.
- Validate upload mime/type/size cho ảnh và CSV.
- Rate limit cho login, forgot password, machine API.
- Password reset token có hạn và one-time use.
- API key machine lưu hash, không lưu plaintext.
- Audit log cho thao tác nhạy cảm.

## Khuyến nghị vận hành
- Bật HTTPS khi triển khai production.
- Đổi admin password mặc định ngay sau cài đặt.
- Đặt giới hạn request ở web server/reverse proxy.
- Theo dõi log `storage/logs` thường xuyên.
