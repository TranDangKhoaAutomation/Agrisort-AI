# RBAC và xác thực

## Ma trận vai trò
- `visitor`: chỉ dùng các route công khai như trang chủ, blog, tài liệu, tra cứu và quét QR.
- `admin`: dùng `/dashboard/admin/*` để quản lý người dùng, CMS, trace, cài đặt và API key.
- `partner`: dùng `/partner/*` để quản lý lô, gói, QR và dữ liệu truy xuất.
- `farmer`: dùng cùng workspace `/partner/*` với phạm vi phù hợp cho nông hộ hoặc đơn vị cung cấp đầu vào.
- `transporter`: cập nhật chặng vận chuyển trong workspace supply.
- `warehouse`: cập nhật chặng kho trong workspace supply.
- `seller`: cập nhật chặng bán ra trong workspace supply.
- `api-key`: dùng cho machine API qua header `X-API-Key`.

## Phê duyệt tài khoản
- Tài khoản đăng ký mới mặc định ở trạng thái `pending`.
- Tài khoản `pending` chưa vào được dashboard nghiệp vụ.
- Quản trị viên duyệt từ màn hình quản lý người dùng.

## Truy xuất công khai
- Mặc định, QR truy xuất được mở công khai để người mua hoặc đối tác quét và xem dữ liệu đã xuất bản.
- Nếu `TRACE_REQUIRE_LOGIN=true`, khách chưa đăng nhập sẽ phải đăng nhập trước khi mở dữ liệu truy xuất.
- Nếu `TRACE_REQUIRE_VIP=true`, hệ thống chuyển sang chế độ yêu cầu mốc truy cập mở rộng cho dữ liệu truy xuất.
- Route `/billing` hiện được giữ cho mục đích tương thích kỹ thuật nhưng nội dung dùng cho hỗ trợ triển khai, không phải trang báo giá dịch vụ.

## Trang tài khoản sau đăng nhập
- `GET /account` hiển thị hồ sơ và trạng thái truy cập hiện tại.
- `POST /account/profile` cập nhật hồ sơ tài khoản.
- `POST /account/password` đổi mật khẩu khi nhập đúng `current_password`.
