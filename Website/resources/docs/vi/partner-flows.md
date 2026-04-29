# Luong nong dan tren prefix legacy `/partner/*`

## 1) Tao lo thu cong
- Mo `/partner/lots`.
- Gui form lo hang.
- He thong tao `qr_token` va tai san QR.
- Trang thai xuat ban phu thuoc `auto_publish_lot`.

## 2) Cap nhat/Xoa lo
- Cap nhat qua `PUT /partner/lots/{id}`.
- Xoa qua `DELETE /partner/lots/{id}`.

## 3) Nhap CSV
- Tai file len `POST /partner/lots/import-csv`.
- Dong hop le se tao lo.
- Dong loi tra ve theo tung dong.

## 4) Tai tao QR
- Goi `POST /partner/lots/{id}/regenerate-qr`.
- Token moi thay token cu.

## 5) Trang QR rieng
- Route: `GET /partner/qr`.
- Tinh nang: danh sach QR lo, tao goi tu lo, tai tao QR goi, cap nhat/xoa goi.

## Quy tac truy cap
- Chi role `farmer` duoc dung workspace nay.
- Role `partner` khong con dung chung dashboard tao lo.
