# Machine API

## Endpoint
`POST /api/v1/machine/lots`

## Required headers
- `X-API-Key: <api_key>`
- `Content-Type: application/json`

## Required fields
- `partner_ref` (farmer email)
- `lot_code`
- `produce_type`
- `origin_region`
- `harvest_date`
- `grade1_count`
- `grade2_count`
- `defect_count`

## Optional fields
- `notes`
- `image_url`

## cURL example
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
    "defect_count":9
  }'
```

## Success response
```json
{
  "success": true,
  "lot_id": 101,
  "qr_token": "abc123token",
  "publish_status": "published",
  "message": "Lot created successfully."
}
```

## Error codes
- `401` missing API key
- `403` invalid API key
- `422` invalid payload
- `429` rate limit exceeded
