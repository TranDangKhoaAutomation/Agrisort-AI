# Thiết lập local

## Yêu cầu
- XAMPP (Apache + MySQL) với PHP 8.1.
- Composer.
- Trình duyệt desktop/mobile để test responsive.

## Khởi chạy nhanh
1. Clone/copy source vào `d:\xampp\htdocs` hoặc `d:\YTKN\website`.
2. Cài dependency:
```bash
composer install
```
3. Tạo file môi trường:
```bash
copy .env.example .env
```
4. Chỉnh `.env`: `APP_URL`, `DB_*`, `SMTP_*`, `GOOGLE_TRANSLATE_API_KEY`.
5. Tạo database và import schema:
```bash
mysql -u root -p < database.sql
```
6. Mở `http://localhost/`.

## Tài khoản mặc định
- Admin email: `admin@gmail.com`
- Admin password: `Admin@123456`

## URL chính để kiểm tra
- `/` landing
- `/auth/login` đăng nhập/đăng ký
- `/dashboard` (role-aware dashboard entrypoint)
- `/dashboard/admin` (admin dashboard)
- `/docs`
