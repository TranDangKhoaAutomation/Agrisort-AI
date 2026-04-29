# README Prompt: Tạo App Android Studio dùng API Website

Tài liệu này là thư viện prompt để làm việc với AI (ChatGPT/Codex/Claude) nhằm sinh app Android bằng **Android Studio** theo kiến trúc rõ ràng, có khả năng mở rộng và bám sát API website.

## 0) Biến đầu vào dùng chung

Thay các placeholder sau trước khi gửi prompt:
- `<BASE_URL>`: ví dụ `https://trandangkhoatechnology.xyz`
- `<APP_NAME>`: ví dụ `AGRISORT Mobile`
- `<PACKAGE_NAME>`: ví dụ `com.agrisort.mobile`
- `<TOKEN>`: access token Bearer nhận từ API login
- `<API_VERSION_PATH>`: mặc định `/api/v1/app`

## 1) Prompt tổng kiến trúc dự án

```text
Bạn là senior Android engineer. Hãy tạo skeleton app Android Studio bằng Kotlin + Jetpack Compose + MVVM cho dự án "<APP_NAME>" (package "<PACKAGE_NAME>").

Yêu cầu:
1) Kiến trúc theo lớp: presentation / domain / data.
2) Networking dùng Retrofit + OkHttp + Kotlinx Serialization (hoặc Moshi).
3) Dùng Coroutines + StateFlow.
4) Có AuthInterceptor tự gắn Bearer token.
5) Có xử lý mã lỗi 401/403/422/429 tập trung.
6) Tách module tính năng: auth, blog, trace, partner, supply, admin.
7) Chuẩn bị sẵn dependency injection (Hilt hoặc Koin).
8) Tạo sẵn theme Compose hỗ trợ tiếng Việt có dấu, tránh lỗi font.
9) Trả về đầy đủ cấu trúc thư mục + mã nguồn mẫu chạy được.

API base:
- BASE_URL: <BASE_URL>
- API path: <API_VERSION_PATH>
```

## 2) Prompt tạo networking chuẩn API website

```text
Tạo lớp networking cho Android app dùng API:
- Base URL: <BASE_URL>
- Prefix endpoint: <API_VERSION_PATH>

Bắt buộc:
1) Retrofit service interface cho toàn bộ endpoint:
   - auth, blog, trace, partner lots/packages, supply events, admin.
2) Data class map đúng response chuẩn:
   {
     success: Boolean,
     message: String,
     data: T?,
     errors: Map<String, Any>?,
     meta: Map<String, Any>?
   }
3) OkHttp interceptor:
   - Gắn Authorization: Bearer <TOKEN> nếu có token.
   - Log request/response ở môi trường debug.
4) Tạo ApiResult sealed class:
   - Success
   - HttpError(code, message, errors)
   - NetworkError
   - UnknownError
5) Viết repository mẫu cho login + list lots + create event.
```

## 3) Prompt module Auth (đăng nhập/đăng ký/hồ sơ)

```text
Tạo module Auth theo MVVM + Compose cho API:
- POST <API_VERSION_PATH>/auth/register
- POST <API_VERSION_PATH>/auth/login
- POST <API_VERSION_PATH>/auth/logout
- GET  <API_VERSION_PATH>/auth/me
- PUT  <API_VERSION_PATH>/auth/profile
- PUT  <API_VERSION_PATH>/auth/password
- POST <API_VERSION_PATH>/auth/forgot-password
- POST <API_VERSION_PATH>/auth/reset-password

Yêu cầu:
1) Màn hình Login, Register, Forgot Password, Profile.
2) Validate dữ liệu phía client bằng tiếng Việt có dấu.
3) Lưu token an toàn bằng DataStore/EncryptedSharedPreferences.
4) Khi app mở lại:
   - nếu token còn hợp lệ -> vào Home.
   - nếu 401 -> chuyển về Login.
5) Trả về code đầy đủ: UI + ViewModel + UseCase + Repository + ApiService mapping.
```

## 4) Prompt module Partner (lô hàng/kiện hàng)

```text
Tạo module Partner cho các API:
- GET    <API_VERSION_PATH>/partner/lots
- POST   <API_VERSION_PATH>/partner/lots
- GET    <API_VERSION_PATH>/partner/lots/{id}
- PUT    <API_VERSION_PATH>/partner/lots/{id}
- DELETE <API_VERSION_PATH>/partner/lots/{id}
- POST   <API_VERSION_PATH>/partner/lots/{id}/regenerate-qr
- GET    <API_VERSION_PATH>/partner/packages
- GET    <API_VERSION_PATH>/partner/packages/{id}
- POST   <API_VERSION_PATH>/partner/packages
- PUT    <API_VERSION_PATH>/partner/packages/{id}
- DELETE <API_VERSION_PATH>/partner/packages/{id}
- POST   <API_VERSION_PATH>/partner/packages/{id}/regenerate-qr

Yêu cầu:
1) Danh sách lô hàng + tìm kiếm + lọc trạng thái.
2) Form tạo/cập nhật lô hỗ trợ upload ảnh multipart.
3) Danh sách kiện hàng theo lô.
4) Màn hình chi tiết hiển thị QR token + trace URL.
5) Toàn bộ text UI bằng tiếng Việt có dấu.
```

## 5) Prompt module Supply (assignments/events + upload chứng từ)

```text
Tạo module Supply cho API:
- GET  <API_VERSION_PATH>/supply/assignments
- GET  <API_VERSION_PATH>/supply/events
- POST <API_VERSION_PATH>/supply/events
- PUT  <API_VERSION_PATH>/supply/events/{id}

Yêu cầu:
1) Danh sách assignment của actor.
2) Tạo/cập nhật event hành trình.
3) Upload nhiều file attachments[] bằng multipart/form-data.
4) Hiển thị link chứng từ đã upload.
5) Xử lý lỗi quyền (403) bằng dialog rõ ràng.
```

## 6) Prompt module Admin đầy đủ

```text
Tạo module Admin cho toàn bộ API:
- dashboard, users, update status, approve partner
- settings get/update
- cms get/update
- blog list/save
- api-keys list/create
- set lot publish
- trace assignments list/save/delete
- trace events list

Yêu cầu:
1) Chỉ hiện module admin nếu role = admin.
2) UI gồm dashboard card + bảng users + form settings/CMS/blog.
3) Quản lý phân công trace và timeline events.
4) Tách từng use case riêng, tránh ViewModel quá lớn.
```

## 7) Prompt kiểm thử

```text
Viết test cho Android app:
1) Unit test:
   - AuthRepository
   - PartnerRepository
   - SupplyRepository
2) ViewModel test:
   - Login success/fail
   - Load lots
   - Create trace event
3) UI test Compose:
   - Login flow
   - Điều hướng theo role
4) Mock API bằng MockWebServer.
5) Báo cáo test coverage tối thiểu 70% cho lớp domain/data.
```

## 8) Prompt build bản phát hành

```text
Hướng dẫn cấu hình build release cho Android Studio:
1) Signing config cho release.
2) Build variant dev/staging/prod.
3) Environment base URL theo build type.
4) Bật R8/Proguard an toàn cho Retrofit/Kotlin serialization.
5) Xuất APK/AAB và checklist kiểm tra trước khi phát hành.
```

## 9) Prompt rà lỗi font tiếng Việt có dấu

```text
Rà soát toàn bộ project Android để tránh lỗi tiếng Việt:
1) Đảm bảo file nguồn UTF-8.
2) String resources không lỗi dấu.
3) Font family hỗ trợ tiếng Việt đầy đủ.
4) Kiểm tra hiển thị trên nhiều thiết bị/emulator.
5) Báo danh sách file có nguy cơ lỗi encoding và cách sửa cụ thể.
```

## 10) Checklist nghiệm thu app sau khi AI sinh mã

1. Login/Register/Logout chạy đúng với API thật.
2. Token Bearer được lưu và gắn request đúng.
3. Role-based navigation hoạt động.
4. CRUD lot/package thành công.
5. Supply event upload attachments[] thành công.
6. Admin màn hình đầy đủ endpoint.
7. Không lỗi font tiếng Việt có dấu.
8. Build debug + release thành công.
