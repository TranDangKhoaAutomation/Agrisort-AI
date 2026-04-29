# README Web 1 Minute

Tài liệu này dùng để giới thiệu nhanh các tác vụ chính của website AGRISORT-AI trong khoảng 45-60 giây.

## Bản nói 1 phút
Chào quý thầy cô cùng các vị ban giám khảo.

"Sau đây em xin giới thiệu về website của chúng em, đây là website AGRISORT-AI hỗ trợ quản lý lô nông sản, gắn mã QR và truy xuất nguồn gốc theo từng vai trò trong chuỗi cung ứng. Ở phần công khai, người dùng có thể xem trang giới thiệu, blog, tài liệu kỹ thuật và quét hoặc tra cứu QR để xem thông tin lô hay gói hàng. Ở phần đối tác và nông hộ, hệ thống cho phép đăng ký tài khoản, tạo và quản lý lô hàng, nhập dữ liệu bằng CSV, tạo gói hàng và tái tạo mã QR. Ở phần chuỗi cung ứng, các vai trò như vận chuyển, kho và người bán có thể cập nhật sự kiện theo từng chặng, kèm thời gian, địa điểm và tệp đính kèm. Ở phần quản trị, admin duyệt tài khoản, điều phối trace, quản lý blog, CMS, cài đặt hệ thống, API key và trạng thái xuất bản lô. Ngoài ra web còn có cổng tài liệu API để kết nối mobile app và máy phân loại."

## Tác vụ có thể sử dụng trên web

### 1. Người dùng công khai
- Xem trang chủ song ngữ VI/EN
- Gửi form liên hệ và yêu cầu demo
- Đọc blog và tài liệu kỹ thuật
- Quét QR hoặc tra cứu token truy xuất
- Xem timeline truy xuất công khai của lô hoặc gói hàng

### 2. Tài khoản thành viên
- Đăng nhập, đăng ký, quên mật khẩu, đặt lại mật khẩu
- Cập nhật thông tin tài khoản
- Đổi mật khẩu

### 3. Đối tác / nông hộ
- Tạo, sửa, xóa lô hàng
- Nhập nhiều lô bằng file CSV
- Tạo và quản lý gói hàng theo lô
- Sinh lại QR cho lô và gói
- Quản lý dữ liệu truy xuất liên quan đến lô/gói hàng của mình

### 4. Vận chuyển / kho / người bán
- Xem các chặng được phân công
- Tạo sự kiện truy xuất theo từng chặng
- Cập nhật thời gian, địa điểm, ghi chú
- Đính kèm ảnh hoặc file PDF cho sự kiện

### 5. Quản trị viên
- Xem dashboard KPI
- Duyệt và khóa/mở tài khoản theo vai trò
- Điều phối trace assignment cho lot/package
- Quản lý cài đặt hệ thống
- Quản lý CMS nội dung website
- Quản lý bài viết blog
- Quản lý API key
- Bật/tắt trạng thái xuất bản của lô

## Gợi ý demo trong 1 phút

1. Mở `/trace` để cho thấy chức năng quét và tra cứu QR.
2. Mở `/partner/lots` để cho thấy khu tạo và quản lý lô hàng.
3. Mở `/partner/qr` để cho thấy QR của lô và gói hàng.
4. Mở `/supply/dashboard` để cho thấy cập nhật sự kiện chuỗi cung ứng.
5. Mở `/dashboard/admin` hoặc `/dashboard/admin/trace` để cho thấy phần quản trị và điều phối.

## Câu chốt ngắn gọn

"Điểm mạnh của web này là quản lý lô nông sản theo vai trò, gắn QR truy xuất, cập nhật hành trình chuỗi cung ứng và sẵn sàng tích hợp với app hoặc máy thông qua API."
