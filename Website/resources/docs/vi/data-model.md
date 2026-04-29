#Mô hình dữ liệu

Tệp lược đồ: `database.sql`.

## Bảng lõi
- `người dùng`
- `hồ sơ đối tác`
- `rất nhiều`
- `lot_import_jobs`
- `demo_request`
- `tin nhắn liên hệ`
- `bài_blog`
- `cms_sections`
- `admin_settings`
- `pass_reset`
- `api_key`
- `bản dịch_cache`
- `log_kiểm toán`

## Quan hệ chính
- `người dùng (id)` 1-n `partner_profiles (user_id)`.
- `người dùng (id)` 1-n `lots (partner_user_id)`.
- `người dùng (id)` 1-n `audit_logs (actor_user_id)`.

## Chỉ mục được đề xuất
- `users.email` duy nhất
- `lots.qr_token` độc đáo
- `blog_posts.slug` độc đáo
- `password_resets.token` duy nhất/được lập chỉ mục
- được lập chỉ mục `api_keys.key_hash`

## Cập nhật mô hình dữ liệu cung cấp dấu vết (27/02/2026)

### vai trò người dùng
- Đã thêm các vai trò: `nông dân`, `người vận chuyển`, `nhà kho`, `người bán`.
- Giữ `đối tác` để tương thích với di sản.

### lô_gói
- Bản ghi gói hàng liên kết với lô (`lot_id`) với `package_code`, `package_label`, `quantity`, `net_weight_kg`, `qr_token`, và `publish_status`.

### bài tập theo dõi
- Kiểm soát tác nhân nào có thể cập nhật giai đoạn nào trên `lot`/`package`.

### dấu_sự kiện
- Truyền tải các sự kiện theo dòng thời gian với `stage_code`, `event_time`, `location_name`, `note` và `actor_user_id`.

### trace_event_attachments
- Lưu trữ tài liệu sự kiện (hình ảnh/pdf) được liên kết bởi `event_id`.
