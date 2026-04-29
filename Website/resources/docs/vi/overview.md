# Tổng quan

AGRISORT-AI là hệ thống phân loại nông sản ứng dụng camera, xử lý ảnh và AI, đồng thời đồng bộ dữ liệu theo mã lô và QR truy xuất nguồn gốc.

## Mục tiêu
- Chuẩn hóa dữ liệu lô hàng sau thu hoạch.
- Hỗ trợ phân loại theo màu sắc, kích thước, độ chín và khuyết tật bề mặt.
- Gắn QR truy xuất với dữ liệu lô để minh bạch chuỗi cung ứng.
- Cung cấp bề mặt tích hợp cho web, app và máy phân loại.

## Chỉ số kỹ thuật chính
- Độ chính xác thử nghiệm: `93-96%`.
- Năng suất định hướng: `1.000-2.000 quả/giờ`.
- Độ trễ quyết định mục tiêu: `<= 200 ms`.
- Giảm lao động phân loại trực tiếp: `50-70%`.

## Phạm vi triển khai
- Hợp tác xã nông nghiệp.
- Trạm thu mua và cơ sở sơ chế sau thu hoạch.
- Doanh nghiệp nông sản nhỏ và vừa cần truy xuất minh bạch theo mã lô.

## Vai trò hệ thống
- `Khách công khai`: xem trang chủ, blog, tài liệu, tra cứu và quét QR.
- `Đối tác` / `Nông hộ`: quản lý lô, gói, QR và dữ liệu truy xuất.
- `Tác nhân chuỗi cung ứng`: cập nhật các chặng vận hành như vận chuyển, kho, bán ra.
- `Quản trị`: duyệt tài khoản, quản lý CMS, blog, settings, trace và API key.

## Mô-đun chính
- Website công khai song ngữ VI/EN.
- Dashboard quản trị và dashboard đối tác.
- Trung tâm quét QR và trang truy xuất công khai.
- App API cho mobile/app nội bộ tại `/api/v1/app/*`.
- Machine API cho máy phân loại tại `POST /api/v1/machine/lots`.
- Cổng tài liệu kỹ thuật tại `/docs`.

## Ghi chú truy xuất công khai
- Luồng quét công khai: `GET /trace`, `POST /trace/scan-upload`, `GET /trace/{token}`.
- Hỗ trợ cả token lô và token gói.
- Người dùng công khai chỉ có quyền đọc dữ liệu đã xuất bản.
- Timeline truy xuất có thể chứa `event_time`, `stage_code`, `location_name`, `note` và tệp đính kèm.
