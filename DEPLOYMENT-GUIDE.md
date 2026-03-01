# Panduan Deployment LMS Misdinar (100+ Siswa)

## 1. Persyaratan Server (Laptop)

### Minimum:
- RAM: 8GB (16GB recommended)
- CPU: 4 cores
- SSD: 10GB free space
- OS: Windows 10/11

### Software Yang Dibutuhkan:
- Laragon (sudah terinstall)
- Cloudflare Tunnel (cloudflared)

---

## 2. Optimasi Laragon untuk 100 User

### A. PHP Configuration (php.ini)
Buka `C:\laragon\bin\php\php-8.x.x\php.ini` dan ubah:

```ini
; Memory & Execution
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
max_input_vars = 5000

; Upload (jika ada)
upload_max_filesize = 10M
post_max_size = 20M

; OPcache (PENTING untuk performa!)
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### B. MySQL Configuration (my.ini)
Buka `C:\laragon\bin\mysql\mysql-8.x.x\my.ini` dan tambahkan di bagian `[mysqld]`:

```ini
[mysqld]
# Connection
max_connections = 200
max_connect_errors = 10000

# Buffer & Cache
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = normal

# Query Cache (untuk MySQL 5.7)
query_cache_type = 1
query_cache_size = 128M

# Temp tables
tmp_table_size = 64M
max_heap_table_size = 64M
```

### C. Apache Configuration (httpd.conf)
Buka `C:\laragon\etc\apache2\httpd.conf`:

```apache
# Enable Keep-Alive
KeepAlive On
MaxKeepAliveRequests 500
KeepAliveTimeout 5

# MPM Settings (cari bagian mpm_winnt_module)
<IfModule mpm_winnt_module>
    ThreadsPerChild 250
    MaxConnectionsPerChild 0
</IfModule>
```

---

## 3. Setup Cloudflare Tunnel

### A. Install Cloudflared
1. Download: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
2. Pilih Windows 64-bit
3. Extract ke folder, misalnya `C:\cloudflared\`

### B. Login ke Cloudflare
```powershell
cd C:\cloudflared
.\cloudflared.exe tunnel login
```
Browser akan terbuka, pilih domain Cloudflare Anda.

### C. Buat Tunnel
```powershell
.\cloudflared.exe tunnel create lms-misdinar
```

### D. Konfigurasi Tunnel
Buat file `config.yml` di `C:\Users\[USERNAME]\.cloudflared\`:

```yaml
tunnel: [TUNNEL-ID-DARI-STEP-C]
credentials-file: C:\Users\[USERNAME]\.cloudflared\[TUNNEL-ID].json

ingress:
  - hostname: lms.yourdomain.com
    service: http://localhost:80
  - service: http_status:404
```

### E. Setup DNS
```powershell
.\cloudflared.exe tunnel route dns lms-misdinar lms.yourdomain.com
```

### F. Jalankan Tunnel
```powershell
.\cloudflared.exe tunnel run lms-misdinar
```

---

## 4. Jalankan Laravel Production Mode

### A. Environment Production
Edit file `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms.yourdomain.com

# Ganti ke file cache untuk performa lebih baik
SESSION_DRIVER=file
CACHE_STORE=file

# Optional: Jika tetap pakai database
# SESSION_DRIVER=database
# CACHE_STORE=database
```

### B. Optimize Laravel
Jalankan di terminal Laragon:

```bash
cd d:\laragon\www\LMS-Misdinar

# Clear semua cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build cache untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optional: Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

## 5. Checklist Sebelum Testing

### Database:
- [ ] `php artisan migrate:fresh --seed` (reset database)
- [ ] Login admin: `admin@misdinar.com` / `admin123`
- [ ] Import data siswa via Admin Panel

### Server:
- [ ] Restart Laragon setelah ubah config
- [ ] Test akses lokal: http://lms-misdinar.test
- [ ] Test akses tunnel: https://lms.yourdomain.com

### Monitoring:
- [ ] Buka Task Manager, pantau CPU & RAM
- [ ] Siapkan backup plan jika server overload

---

## 6. Tips Saat Testing Berlangsung

### Sebelum Mulai:
1. Tutup aplikasi lain yang tidak perlu
2. Matikan Windows Update
3. Colok charger laptop
4. Gunakan koneksi WiFi stabil atau kabel LAN

### Saat Ujian:
1. Jangan buka aplikasi berat di laptop server
2. Pantau Task Manager
3. Jika CPU > 90% terus-menerus, pertimbangkan:
   - Kurangi polling interval di frontend
   - Tutup tab monitor yang tidak perlu

### Troubleshooting:
- **Error 502/504**: Restart Apache di Laragon
- **Database timeout**: Restart MySQL di Laragon  
- **Tunnel disconnect**: Jalankan ulang `cloudflared tunnel run`

---

## 7. Alternatif: Ngrok (Backup)

Jika Cloudflare Tunnel bermasalah:

```bash
# Install ngrok
choco install ngrok

# Login (gratis)
ngrok config add-authtoken YOUR_TOKEN

# Jalankan
ngrok http 80
```

Catatan: Ngrok gratis terbatas untuk 40 koneksi simultan.

---

## 8. Perintah Cepat

```powershell
# Start server lokal
cd d:\laragon\www\LMS-Misdinar

# Reset database (HATI-HATI!)
php artisan migrate:fresh --seed

# Clear cache
php artisan cache:clear

# Monitoring real-time (Windows)
Get-Process | Sort-Object CPU -Descending | Select-Object -First 10

# Test koneksi database
php artisan tinker
>>> DB::connection()->getPdo()
```

---

## 9. Kontak Darurat

Jika ada masalah teknis saat testing:
- Restart Laragon
- Restart Cloudflared
- Check error log: `storage/logs/laravel.log`

Good luck! 🚀
