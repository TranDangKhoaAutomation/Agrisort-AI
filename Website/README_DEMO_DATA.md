# Demo Data

Run `php scripts/seed_demo_data.php` to refresh the local demo dataset.

All demo accounts use the same password: `Admin@123456`

Accounts:
- `agrisort.demo.admin@gmail.com` - admin
- `agrisort.demo.partner@gmail.com` - partner
- `agrisort.demo.farmer@gmail.com` - farmer
- `agrisort.demo.transport@gmail.com` - transporter
- `agrisort.demo.warehouse@gmail.com` - warehouse
- `agrisort.demo.seller@gmail.com` - seller

What the script prepares:
- 5 demo lot records with sample product images
- 5 demo package records with QR images
- sample trace assignments and events for transporter, warehouse, and seller
- image files in `public/uploads/demo/`
- QR files in `public/qr/`

Image sources:
- `mango-demo.jpg`: https://commons.wikimedia.org/wiki/File:Mangoes_,,.jpg
- `avocado-demo.jpg`: https://commons.wikimedia.org/wiki/File:Avocado_fruitnfoliage.jpg
- `dragonfruit-demo.jpg`: https://commons.wikimedia.org/wiki/File:Dragonfruit_Chiayi_market.jpg
- `orange-demo.jpg`: https://commons.wikimedia.org/wiki/File:Citrus_sinensis.jpg
- `banana-demo.jpg`: https://commons.wikimedia.org/wiki/File:Bananas_fruit_(1).jpg

These addresses are fake Gmail-style demo identities intended for local testing only.
