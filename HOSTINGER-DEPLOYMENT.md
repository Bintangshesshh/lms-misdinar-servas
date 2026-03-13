# Deploy LMS-Misdinar ke Hostinger (Minim Error)

Panduan ini dibuat supaya project lokal kamu bisa dipindah ke Hostinger dengan risiko error serendah mungkin.

## 1. Target Struktur Folder (Hostinger)

Gunakan struktur ini (recommended):

- `~/domains/namadomainkamu.com/lms-misdinar` -> source Laravel (private)
- `~/domains/namadomainkamu.com/public_html` -> web root (public)

Jangan taruh semua source Laravel langsung di `public_html`.

## 2. Preflight di Lokal (WAJIB)

Jalankan ini dari project lokal sebelum upload:

```powershell
cd d:\laragon\www\LMS-Misdinar

php artisan optimize:clear
php artisan migrate --pretend
php artisan route:list
php artisan about
npm run build
```

Kalau ada error di tahap ini, bereskan dulu di lokal sebelum deploy.

## 3. Upload ke Hostinger

## Opsi A (Paling Aman): Ada SSH

1. Upload source project ke folder `lms-misdinar` (pakai Git atau SFTP).
2. Masuk SSH lalu jalankan:

```bash
cd ~/domains/namadomainkamu.com/lms-misdinar
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

3. Copy isi folder `public/` ke `public_html/`.
4. Edit `public_html/index.php` bagian path:

```php
require __DIR__.'/../lms-misdinar/vendor/autoload.php';
$app = require_once __DIR__.'/../lms-misdinar/bootstrap/app.php';
```

5. Pastikan `public_html/.htaccess` sama seperti `public/.htaccess` dari Laravel.

## Opsi B: Tanpa SSH (File Manager/SFTP saja)

1. Di lokal jalankan:

```powershell
cd d:\laragon\www\LMS-Misdinar
composer install --no-dev --optimize-autoloader
npm run build
php artisan optimize:clear
```

2. Upload semua source ke folder `lms-misdinar` di Hostinger.
3. Copy isi `public/` ke `public_html/`.
4. Edit `public_html/index.php` path ke folder `lms-misdinar` (contoh di atas).
5. Upload `.env` production.

Catatan: opsi tanpa SSH lebih rawan salah upload, jadi cek ulang path `vendor/autoload.php` dan `bootstrap/app.php`.

### Opsi B1: Jalankan Cache Production Tanpa SSH (One-Time Endpoint Aman)

Jika paket hosting tidak menyediakan SSH, gunakan endpoint internal sekali pakai ini.

1. Di `.env` server, set sementara:

```env
DEPLOY_CACHE_WARM_ENABLED=true
DEPLOY_CACHE_WARM_TOKEN=token_acak_panjang_min_32_char
DEPLOY_CACHE_WARM_ALLOW_GET=true
DEPLOY_CACHE_WARM_INCLUDE_ROUTE=false
```

2. Buka URL berikut di browser (ganti domain + token):

```text
https://namadomainkamu.com/_internal/deploy/cache-warm?token=token_acak_panjang_min_32_char
```

3. Pastikan respons JSON menunjukkan `ok: true`.
4. Setelah sukses, ubah lagi `.env`:

```env
DEPLOY_CACHE_WARM_ENABLED=false
```

Endpoint ini otomatis terkunci setelah sukses (lock file), jadi tidak bisa dijalankan ulang sembarang.

## 4. Template .env Production (Hostinger)

Gunakan referensi dari file `.env.hostinger.example` di repo ini.

Alur sinkron paling aman:

1. Update nilai non-rahasia langsung di `.env.hostinger.example` lalu commit ke repo.
2. Untuk nilai rahasia (`APP_KEY`, `DB_PASSWORD`), isi langsung di `.env` server Hostinger (jangan commit ke Git).
3. Setiap deploy, bandingkan `.env.hostinger.example` terbaru dengan `.env` di server lalu update yang berubah.

Poin penting:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://misdinarservas.cloud`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=sync` (sesuai paket hosting saat ini)
- set `TRUSTED_PROXIES` jika ada reverse proxy

## 5. Permission Wajib

Pastikan writable:

- `storage/`
- `bootstrap/cache/`

Jika ada SSH:

```bash
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;
```

## 6. Validasi Setelah Deploy

Cek endpoint ini:

- `/up` -> harus 200
- `/login` -> tampil normal
- login admin -> dashboard kebuka
- buka monitor ujian -> polling jalan
- login siswa dari device lain -> join lobby muncul di monitor
- start ujian -> countdown pindah ke started

## 7. Error Paling Sering + Solusi Cepat

- `500 APP_KEY missing`
  - Jalankan `php artisan key:generate --force` lalu clear cache config.

- `Class "PDO" not found` atau ext kurang
  - Aktifkan ekstensi PHP di hPanel (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `tokenizer`, `xml`).

- `No application encryption key has been specified`
  - `.env` tidak terbaca atau path salah. Cek lokasi `.env` harus di root Laravel (`lms-misdinar/.env`).

- CSS/JS tidak muncul
  - Pastikan `public/build` ikut ke-upload.

- `419 Page Expired`
  - Cek domain mismatch (`APP_URL`), cookie/session, dan pastikan request dari domain yang sama.

## 8. Rekomendasi untuk 100 Siswa

- Gunakan paket hosting yang resource CPU/RAM cukup (minimal paket Business atau VPS).
- Jangan pakai `APP_DEBUG=true` di produksi.
- Gunakan `database`/`redis` untuk session dan cache.
- Uji bertahap: 20 -> 50 -> 100 user sebelum hari H.

## 9. Checklist Anti-503 (Hari H Ujian)

- Set `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `EXAM_LOAD_PROFILE=crowded`, `EXAM_MIN_POLL_MS=5000`.
- Pastikan polling admin tidak di bawah 5 detik (`EXAM_ADMIN_POLL_* >= 5000`).
- Jalankan cache build sebelum ujian: `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
- Pastikan `storage/` dan `bootstrap/cache/` writable.
- Validasi cepat 2 sisi: 1 akun admin buka monitor + 2 akun siswa aktifkan autosave.
- Hindari deploy mendadak saat ujian berlangsung.
- Jika terjadi lonjakan, naikkan polling admin dulu (mis. `EXAM_ADMIN_POLL_RUNNING_LARGE_MS=12000`).
