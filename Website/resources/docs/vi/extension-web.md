# Mở rộng Web

## Quy trình thêm tính năng
1. Thêm route trong `routes/web.php`.
2. Tạo action trong controller tương ứng.
3. Thêm model/service nếu có logic dữ liệu.
4. Tạo view trong `resources/views`.
5. Bổ sung key i18n tại `resources/lang/vi.php` và `resources/lang/en.php`.
6. Cập nhật `resources/docs/route_catalog.php`.
7. Cập nhật markdown docs liên quan.
8. Chạy test docs consistency và smoke test.

## Quy tắc tương thích
- Không đổi contract route cũ nếu chưa version hóa.
- Nếu bắt buộc đổi request/response, thêm endpoint mới hoặc version mới.
- Giữ fallback dữ liệu để không phá client cũ.

## Styling
- Dùng biến màu trong `public/css/app.css`.
- Tránh viết inline style trong view.
- Kiểm tra responsive ở breakpoint mobile.
