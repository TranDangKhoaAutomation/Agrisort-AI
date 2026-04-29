# App extension

## JavaScript (fetch)
```js
const payload = {
  partner_ref: "partner@example.com",
  lot_code: "APP-LOT-001",
  produce_type: "Mango",
  origin_region: "Dong Thap",
  harvest_date: "2026-02-20",
  grade1_count: 120,
  grade2_count: 40,
  defect_count: 3
};

const res = await fetch("http://localhost/api/v1/machine/lots", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-API-Key": "agri_xxxxx"
  },
  body: JSON.stringify(payload)
});
console.log(await res.json());
```

## Flutter (Dart + http)
```dart
final uri = Uri.parse('http://localhost/api/v1/machine/lots');
final response = await http.post(
  uri,
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'agri_xxxxx',
  },
  body: jsonEncode({
    'partner_ref': 'partner@example.com',
    'lot_code': 'APP-LOT-001',
    'produce_type': 'Mango',
    'origin_region': 'Dong Thap',
    'harvest_date': '2026-02-20',
    'grade1_count': 120,
    'grade2_count': 40,
    'defect_count': 3,
  }),
);
print(response.body);
```

## Kotlin (OkHttp)
```kotlin
val client = OkHttpClient()
val json = """
{
  "partner_ref":"partner@example.com",
  "lot_code":"APP-LOT-001",
  "produce_type":"Mango",
  "origin_region":"Dong Thap",
  "harvest_date":"2026-02-20",
  "grade1_count":120,
  "grade2_count":40,
  "defect_count":3
}
""".trimIndent()

val request = Request.Builder()
  .url("http://localhost/api/v1/machine/lots")
  .addHeader("Content-Type", "application/json")
  .addHeader("X-API-Key", "agri_xxxxx")
  .post(json.toRequestBody("application/json".toMediaType()))
  .build()

client.newCall(request).execute().use { println(it.body?.string()) }
```

## Notes
- Mobile apps cannot call `localhost` directly from real devices.
- Use LAN IP or public domain.
- Enforce HTTPS in production.
