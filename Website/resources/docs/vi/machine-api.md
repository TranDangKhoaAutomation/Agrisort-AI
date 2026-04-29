# Machine API

## Endpoint
`POST /api/v1/machine/lots`

## Header bắt buộc
- `X-API-Key: <api_key>`
- `Content-Type: application/json`

## Field bắt buộc
- `partner_ref` (email farmer)
- `lot_code`
- `produce_type`
- `origin_region`
- `harvest_date`
- `grade1_count`
- `grade2_count`
- `defect_count`

## Field tùy chọn
- `notes`
- `image_url`

## Ví dụ cURL
```bash
BASE_URL=https://trandangkhoatechnology.xyz
API_KEY=agri_xxxxx

curl -X POST "$BASE_URL/api/v1/machine/lots" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{
    "partner_ref":"farmer@example.com",
    "lot_code":"MACHINE-LOT-001",
    "produce_type":"Mango",
    "origin_region":"Dong Thap",
    "harvest_date":"2026-02-20",
    "grade1_count":320,
    "grade2_count":80,
    "defect_count":9,
    "notes":"Imported from edge"
  }'
```

## Response thành công
```json
{
  "success": true,
  "lot_id": 101,
  "qr_token": "abc123token",
  "publish_status": "published",
  "message": "Lot created successfully."
}
```

## Mã lỗi
- `401`: thiếu API key.
- `403`: API key sai/không hoạt động.
- `422`: payload sai.
- `429`: vượt rate limit.
