# Kiến trúc

## Luồng request
1. Request vào `index.php`.
2. `Request::capture()` chuẩn hóa method/path.
3. Router map sang controller action trong `routes/web.php` hoặc `routes/api.php`.
4. Middleware logic nằm ở Router options (`auth`, `role`, `csrf`).
5. Controller gọi model/service và render view hoặc JSON.

## Tầng chính
- `app/Core`: Request, Router, Response, View, Auth, CSRF.
- `app/Controllers`: public, auth, partner, admin, machine API, docs.
- `app/Models`: truy cập CSDL qua PDO prepared statements.
- `app/Services`: CSV, QR, mail, translate, audit, docs builder.
- `resources/views`: giao diện PHP template.

## Hồ sơ kỹ thuật hệ thống (theo tài liệu dự án)
- Pipeline: Camera -> Xử lý ảnh -> Mô hình AI -> Quyết định phân loại -> Cơ cấu gạt.
- Độ chính xác thử nghiệm: `93-96%`.
- Năng suất mục tiêu: `1.000-2.000 quả/giờ`.
- Thời gian quyết định mục tiêu: `<= 200 ms`.
- Triển khai ưu tiên: HTX, cơ sở sơ chế, doanh nghiệp nhỏ-vừa.

## Đội ngũ triển khai công khai
- Nguyễn Khắc Tùng Lâm - Team Lead / System Integration.
- Nguyễn Đăng Quang - AI & Data Pipeline.
- Võ Thị Mỹ - Business & Market Analysis.
- Trần Đăng Khoa - Embedded & Control.
- Lê Hữu Trăng - Mechanical & Automation.
- KS. Vũ Văn Dũng - Advisor.
- ThS. Nguyễn Thị Thành - Advisor.

## Dữ liệu docs
- Markdown section trong `resources/docs/{vi|en}`.
- `manifest.php` quản lý thứ tự và tiêu đề section.
- `route_catalog.php` là nguồn chuẩn endpoint + schema.
- DocsService build OpenAPI/Postman từ catalog tại runtime.
