# Sistem Jualan Online Tanpa Cart

## Context

Aplikasi ini baru sahaja di-scaffold (Laravel 13 + Fortify + Livewire 4 + Flux UI + Pest), cuma ada auth (login/register/2FA/passkeys) — belum ada sebarang produk/jualan. Objektif: bina sistem jualan di mana setiap produk ada landing page sendiri dengan borang tempahan; bila customer submit borang, terus ke halaman pembayaran (tiada cart, tiada proses checkout berbilang langkah). Produk boleh fizikal (perlu shipping) atau digital (perlu delivery fail/link). Pembayaran buat masa ini **manual** (customer transfer bank/QR, admin confirm secara manual dalam admin panel) tetapi direka supaya gateway sebenar (ToyyibPay/Billplz/Stripe) boleh ditambah kemudian tanpa ubah struktur order. Juga perlu halaman katalog produk awam, dan admin panel penuh (Livewire) untuk urus produk & order.

Tiada role/permission, payment, file-upload, atau admin-panel package dipasang lagi — semuanya akan dibina guna apa yang sedia ada (Livewire 4 SFC pattern, Flux UI, Tailwind v4) supaya konsisten dengan konvensyen app ini. **Tiada dependency baru diperlukan** untuk plan ini.

## 1. Setup Awal

- Jalankan `php artisan storage:link` (belum dijalankan lagi — perlu sebelum imej produk boleh diakses).

## 2. Enums (`app/Enums/`)

- `ProductType.php` — `Physical`, `Digital`
- `PaymentStatus.php` — `Pending`, `Paid`, `Failed`, `Refunded`
- `OrderStatus.php` — `New`, `Processing`, `Shipped`, `Delivered`, `Completed`, `Cancelled`

## 3. Migrations & Models

**`add_is_admin_to_users_table`** — `boolean('is_admin')->default(false)`. Tiada package permission (spatie) — satu boolean cukup sebab cuma satu peranan admin diperlukan.

**`create_products_table`** — `name`, `slug` (unique, route-key), `description`, `price_cents` (int, elak isu float), `currency` (default `MYR`), `type` (enum cast), `images` (json array path public disk), `digital_file_path`, `digital_access_url`, `is_active`.

**`create_orders_table`** — `order_number` (unique, auto-generate), `product_id` (FK, restrictOnDelete), snapshot fields (`product_name_snapshot`, `price_cents_snapshot`, `currency_snapshot`, `product_type_snapshot` — supaya order lama tak terjejas bila produk diubah/dipadam), customer fields (`customer_name`, `customer_email`, `customer_phone`), shipping fields nullable (`shipping_address_line1/2`, `city`, `state`, `postcode`, `country`), `payment_method` (default `manual`), `payment_status` (enum cast), `payment_reference`, `paid_at`, `payment_confirmed_by` (FK users nullable), `status` (fulfillment enum, terpisah dari payment_status), `tracking_number`, `shipped_at`, `digital_access_token` (unique, untuk delivery digital tanpa perlu login).

**`app/Models/Product.php`** — casts (`images`→array, `type`→enum, `is_active`→bool), `getRouteKeyName()` → `slug`, auto-generate slug unik dari `name` bila creating.

**`app/Models/Order.php`** — casts enum, `belongsTo(Product::class)`, boot `creating` hook generate `order_number` + `digital_access_token`, method `markAsPaid(?User $confirmedBy = null)` yang set `payment_status`, `paid_at`, `payment_confirmed_by`, dan fire event `OrderPaid` — **satu titik masuk sahaja** untuk "order dah bayar", supaya admin manual-confirm dan gateway callback nanti guna method yang sama.

**Factories**: `ProductFactory` (states `physical()`, `digital()`), `OrderFactory` (states `paid()`, `pending()`, `forProduct()`).

## 4. Access Control (Admin)

- `app/Http/Middleware/EnsureUserIsAdmin.php` — abort 403 jika `!auth()->user()->is_admin`.
- Daftar alias `admin` di `bootstrap/app.php` dalam closure `->withMiddleware(function (Middleware $middleware) { $middleware->alias(['admin' => EnsureUserIsAdmin::class]); })` (closure ini kosong sekarang, tambah di situ).
- `Gate::define('access-admin', ...)` di `AppServiceProvider` (opsyenal, untuk guna di Blade `@can`).
- Tiada UI pendaftaran admin — admin flag di-set via seeder/tinker sahaja.

## 5. Payment Abstraction (`app/Payments/`)

- `Contracts/PaymentGateway.php` — interface `initiate(Order $order): PaymentInitiationResult`, `handleCallback(Request $request): void`.
- `PaymentInitiationResult.php` — DTO simple (`redirectUrl`).
- `ManualPaymentGateway.php implements PaymentGateway` — `initiate()` return redirect ke `shop.orders.payment` (halaman kita sendiri); `handleCallback()` no-op.
- `config/payments.php` — `default` (`env('PAYMENT_GATEWAY', 'manual')`), `gateways` map key→class.
- Borang order resolve gateway ikut `config('payments.default')` (atau `payment_method` per order), bukan hardcode 'manual' — bila gateway sebenar ditambah kemudian, cuma tambah class baru + config, tiada ubah pada Order/borang.
- `app/Events/OrderPaid.php` + `app/Listeners/SendOrderPaidNotification.php` (stub/log dulu, hook point untuk emel kemudian).

## 6. Halaman Awam (Storefront)

Layout baru `resources/views/layouts/shop.blade.php` (ringkas, header+footer, bukan sidebar authenticated shell) — bukan reuse `layouts/app.blade.php` sebab itu shell authenticated dengan sidebar, tak sesuai untuk guest storefront.

Routes baru `routes/shop.php` (required dari `web.php`, sama pattern macam `settings.php`):
- `GET /` → `pages.shop.⚡index` (katalog, `Product::where('is_active', true)`)
- `GET /products/{product:slug}` → `pages.shop.⚡product-show` (landing page + borang tempahan; 404 jika tak aktif; medan shipping tunjuk conditional jika `type === physical`; submit → create Order (snapshot dari produk) → resolve payment gateway → redirect)
- `GET /orders/{order:order_number}/payment` → `pages.shop.⚡order-payment` (arahan bayar manual, medan reference bayaran customer, papar link muat turun jika `payment_status === paid` & digital)
- `GET /downloads/{order:order_number}/{token}` → `DigitalDownloadController` (invokable, validate token + `payment_status === paid` + `type === digital`, stream fail atau redirect ke `digital_access_url`)

Route `Route::view('/', 'welcome')` sedia ada di `web.php` akan **digantikan** — `/` terus jadi katalog produk (`pages.shop.⚡index`), `welcome.blade.php` & route lama dibuang.

## 7. Admin Panel

Reuse `layouts/app.blade.php` (sidebar shell sedia ada) — tambah seksyen "Admin" dalam `resources/views/layouts/app/sidebar.blade.php` (`<flux:sidebar.group>` baru), conditional `@if(auth()->user()->is_admin)`.

Routes baru `routes/admin.php`, group `['auth', 'verified', 'admin']`, prefix `admin`:
- `⚡dashboard` — ringkasan stats
- `products/⚡index`, `⚡create`, `⚡edit` — guna `app/Livewire/Forms/ProductForm.php` (Livewire Form Object) untuk shared validation create/edit; upload imej guna `WithFileUploads` (built-in Livewire, tiada package baru)
- `orders/⚡index` (list + filter status), `⚡show` (butang "Mark as Paid" → `Order::markAsPaid()`, update fulfillment status, tracking number untuk fizikal)

## 8. Testing (Pest, `tests/Feature/` & `tests/Unit/`)

- Storefront: katalog papar produk aktif sahaja, landing page 404 untuk slug tak aktif, borang tempahan (validation + medan shipping conditional), submit cipta Order dengan snapshot betul & redirect ke payment page.
- Payment page: papar arahan manual, link download muncul bila `paid`.
- Digital download: tolak order belum bayar/token salah, terima order digital yang dah `paid`.
- Admin access control: bukan-admin/guest kena 403/redirect dari semua `/admin/*`.
- Admin: Product CRUD (create/edit/delete, slug unik, upload imej), Order management (mark as paid fire `OrderPaid` event via `Event::fake()`, update fulfillment status).
- Unit: `ManualPaymentGateway`, `Order::markAsPaid()`.

Jalankan `php artisan test --compact --filter=...` selepas setiap kumpulan test dicipta.

## 9. Turutan Bina

1. Setup (`storage:link`) + Enums
2. Migrations + Models + Factories (`is_admin`, `products`, `orders`)
3. Access control (middleware + route group, walau UI belum ada)
4. Payment abstraction (interface, ManualPaymentGateway, config, event) + unit test
5. Admin: Product CRUD (perlu wujud dulu sebelum storefront ada produk untuk dipaparkan)
6. Storefront: layout, katalog, landing page + borang, payment page, digital download controller
7. Admin: Order management (mark as paid, fulfillment status)
8. End-to-end test: browse katalog → landing page → submit order → payment page → admin confirm → digital unlock/status fizikal
9. `vendor/bin/pint --dirty --format agent` selepas setiap perubahan PHP; run full Pest suite di akhir

## Verifikasi

- `php artisan test --compact` — semua test baru & sedia ada pass.
- `vendor/bin/pint --format agent` — bersih.
- Manual: `php artisan serve` / Herd, layari katalog → produk fizikal → isi borang → payment page; ulang untuk produk digital → confirm bayaran (guna tinker/admin UI) → cuba link download.
- Guna `mcp__laravel-boost__database-query` untuk semak data order/produk selepas test manual, `browser-logs` jika ada isu JS/Livewire.
