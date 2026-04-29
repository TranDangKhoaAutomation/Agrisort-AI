# Kiểm thử

## Test script
```bash
php tests/run.php
php tests/docs_consistency.php
```

## Smoke tests bắt buộc
1. Partner đăng ký mới chưa duyệt không vào dashboard.
2. Admin duyệt partner, partner tạo lô thành công.
3. CSV import báo lỗi đúng số dòng.
4. Auto publish bật/tắt phản ánh đúng trạng thái lô.
5. `/trace/{qr_token}` hiển thị đúng dữ liệu.
6. Contact/tư vấn lưu DB và hiển thị admin.
7. Forgot/reset password hợp lệ, token hết hạn đúng.
8. CMS VI -> auto translate EN render đúng ngôn ngữ.
9. Blog publish và truy cập slug được.
10. Machine API key đúng tạo lô, key sai trả 401/403.
11. Route trái quyền trả 403.
12. Docs route consistency pass.

## Regression checklist
- Desktop + mobile responsive.
- Kiểm tra nút copy code trong `/docs`.
- Kiểm tra menu Docs cho user login và guest.
