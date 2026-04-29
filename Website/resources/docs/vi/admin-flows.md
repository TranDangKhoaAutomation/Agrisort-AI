# Luồng quản trị

## KPI của bảng điều khiển
- Đối tác đang chờ xử lý.
- Tổng số lô.
- Khách hàng tiềm năng mới (liên hệ/tư vấn).
- Số lượng bài đăng trên blog.

## Hoạt động chính
- Phê duyệt/chặn tài khoản đối tác.
- Quản lý cài đặt (`auto_publish_lot`, SMTP, URL cơ sở QR, khóa dịch).
- Quản lý nội dung CMS đích (VI/EN + dịch tự động).
- Tạo/cập nhật/xuất bản bài viết blog.
- Tạo key API của máy.
- Chuyển đổi trạng thái xuất bản lô.

## Khả năng kiểm toán
Các hành động nhạy cảm phải được ghi lại trong `aud_logs` để truy xuất nguồn gốc.

## Cập nhật luồng quản trị viên theo dõi (27/02/2026)

### Tuyến đường mới
- `NHẬN/bảng điều khiển/quản trị viên/dấu vết`
- `POST /dashboard/admin/users/{id}/status`
- `POST/bảng điều khiển/quản trị viên/trace/bài tập`
- `XÓA/bảng điều khiển/quản trị viên/trace/bài tập/{id}`

### Khả năng
- Phê duyệt các tác nhân đang chờ phê duyệt: nông dân/người vận chuyển/kho/người bán.
- Gán các giai đoạn cho các thực thể `lot` hoặc `package`.
- Giám sát dòng thời gian trên toàn hệ thống và các tệp đính kèm sự kiện.
