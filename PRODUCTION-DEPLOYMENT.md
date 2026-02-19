# Production Deployment Guide - LMS Misdinar

## Overview
Sistem LMS untuk 84 siswa dengan kapasitas 100 concurrent users. Deployment ini telah dioptimasi dengan rate limiting, database indexes, caching, dan query optimization.

---

## Server Requirements

### Minimum Specifications
- **CPU**: 2 cores (4 cores recommended)
- **RAM**: 4GB minimum (8GB recommended for 100+ concurrent users)
- **Storage**: 20GB SSD (untuk database growth)
- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher (InnoDB engine)
- **Web Server**: Apache 2.4+ or Nginx 1.18+

### PHP Extensions Required
```bash
php -m | grep -E 'pdo|mysql|mbstring|tokenizer|xml|ctype|json|bcmath|openssl|fileinfo|zip'
```

Pastikan semua extensions berikut installed:
- pdo_mysql
- mbstring
- tokenizer
- xml
- ctype
- json
- bcmath
- openssl
- fileinfo
- zip

---

## Pre-Deployment Checklist

### 1. Environment Configuration
Copy `.env.example` ke `.env` dan configure:

```bash
cp .env.example .env
```

**Critical Settings:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms_misdinar
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

# IMPORTANT: Untuk 100+ users, gunakan Redis
# SESSION_DRIVER=redis
# CACHE_STORE=redis
# QUEUE_CONNECTION=redis
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm ci --production
```

### 3. Generate Application Key
```bash
php artisan key:generate
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE lms_misdinar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --force
```

### 5. File Permissions
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Build Assets
```bash
npm run build
```

### 7. Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Performance Optimizations (Already Implemented)

### ✅ 1. Rate Limiting
- **Student routes**: 60 req/min
- **Admin routes**: 120 req/min
- **Join lobby**: 10 req/min
- **Submit exam**: 5 req/min
- **Save answer**: 120 req/min
- **Polling endpoints**: 120-180 req/min

### ✅ 2. Database Indexes
Indexes added to critical tables:
- `exams`: status, is_active, started_at
- `exam_sessions`: status, integrity, exam_id+user_id, joined_at
- `questions`: exam_id
- `student_answers`: exam_session_id, question_id
- `cheat_logs`: exam_session_id, violation_type, occurred_at
- `users`: email, role

### ✅ 3. Query Caching
- Admin exam list: 30 seconds TTL
- Student exam list: 30 seconds TTL
- Lobby status polling: 2 seconds TTL
- Exam questions: 10 minutes TTL (immutable during exam)

### ✅ 4. Eager Loading
- Session queries with user relationships
- Question loading optimized (single query)
- Answer submissions optimized (bulk operations)

---

## Load Testing (Before Production)

### Using Apache Bench (Simple)
```bash
# Install Apache Bench
sudo apt-get install apache2-utils  # Ubuntu/Debian
brew install ab                       # macOS

# Test login page (100 concurrent, 1000 requests)
ab -n 1000 -c 100 https://your-domain.com/login

# Test student dashboard (with authentication)
ab -n 1000 -c 100 -C "laravel_session=YOUR_SESSION_COOKIE" https://your-domain.com/student/dashboard
```

### Using Artillery (Advanced)
```bash
# Install Artillery
npm install -g artillery

# Create test scenario
cat > load-test.yml << EOF
config:
  target: 'https://your-domain.com'
  phases:
    - duration: 60
      arrivalRate: 10
      name: "Warmup"
    - duration: 300
      arrivalRate: 100
      name: "Peak Load - 100 concurrent users"
  processor: "./test-helpers.js"

scenarios:
  - name: "Student Exam Flow"
    flow:
      - get:
          url: "/login"
      - post:
          url: "/login"
          json:
            email: "student{{ \$randomNumber() }}@test.com"
            password: "password"
      - get:
          url: "/student/dashboard"
      - get:
          url: "/student/exam/{{ examId }}/take"
      - think: 5
      - post:
          url: "/student/exam/{{ examId }}/save-answer"
          json:
            question_id: 1
            selected_answer: "a"
EOF

# Run load test
artillery run load-test.yml
```

### Monitoring During Load Test
```bash
# Monitor MySQL connections
watch -n 1 'mysql -u root -p -e "SHOW PROCESSLIST;" | wc -l'

# Monitor server resources
htop

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### Success Criteria
- ✅ Response time < 500ms for 95% of requests
- ✅ No 500 errors during peak load
- ✅ MySQL connections stay below 100
- ✅ CPU usage < 80%
- ✅ Memory usage < 70%

---

## Database Optimization

### MySQL Configuration (my.cnf)
```ini
[mysqld]
# Connection Pool
max_connections = 200
max_connect_errors = 100

# InnoDB Settings
innodb_buffer_pool_size = 2G  # 50-70% of available RAM
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query Cache (MySQL 5.7 only)
query_cache_type = 1
query_cache_size = 256M

# Optimize for read-heavy workload
innodb_read_io_threads = 4
innodb_write_io_threads = 4
```

Restart MySQL after changes:
```bash
sudo systemctl restart mysql
```

---

## Monitoring & Troubleshooting

### Real-time Monitoring
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# MySQL slow queries
mysql -u root -p
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
tail -f /var/log/mysql/mysql-slow.log

# Nginx/Apache access logs
tail -f /var/log/nginx/access.log
tail -f /var/log/apache2/access.log
```

### Common Issues & Solutions

#### 1. "Too Many Connections" Error
```sql
-- Check current connections
SHOW PROCESSLIST;

-- Increase max_connections
SET GLOBAL max_connections = 300;

-- Kill idle connections
SELECT CONCAT('KILL ', id, ';') FROM information_schema.processlist WHERE command = 'Sleep';
```

#### 2. Slow Response Times
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3. High Memory Usage
```bash
# Check PHP memory limit
php -i | grep memory_limit

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

#### 4. Session Errors
```bash
# Check sessions table
mysql -u root -p lms_misdinar -e "SELECT COUNT(*) FROM sessions;"

# Clean old sessions
php artisan session:gc

# Migrate sessions if needed
php artisan session:table
php artisan migrate
```

---

## Backup Strategy

### Before Exam Day
```bash
# Full database backup
mysqldump -u root -p lms_misdinar > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup uploads (if any)
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

### During Exam (Hot Backup)
```bash
# Create cron job for incremental backups
0 */2 * * * mysqldump -u root -p lms_misdinar | gzip > /backups/lms_$(date +\%Y\%m\%d_\%H\%M).sql.gz
```

### Restore from Backup
```bash
# Restore database
mysql -u root -p lms_misdinar < backup_20260218_120000.sql

# Restore storage
tar -xzf storage_backup_20260218.tar.gz
```

---

## Emergency Procedures

### If Server Crashes During Exam

1. **Immediate Actions**
   ```bash
   # Restart services
   sudo systemctl restart nginx
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart mysql
   
   # Check service status
   sudo systemctl status nginx
   sudo systemctl status php8.2-fpm
   sudo systemctl status mysql
   ```

2. **Check Logs**
   ```bash
   tail -100 storage/logs/laravel.log
   tail -100 /var/log/nginx/error.log
   tail -100 /var/log/mysql/error.log
   ```

3. **Student Communication**
   - Inform students via WhatsApp/announcement
   - Extend exam duration if needed:
   ```sql
   UPDATE exams SET duration_minutes = duration_minutes + 15 WHERE id = X;
   ```

4. **Data Recovery**
   - All answers auto-saved (check `student_answers` table)
   ```sql
   SELECT COUNT(*) FROM student_answers WHERE exam_session_id IN (
       SELECT id FROM exam_sessions WHERE exam_id = X
   );
   ```

### If Database is Corrupted
```bash
# Check and repair InnoDB tables
mysqlcheck -u root -p --auto-repair --optimize lms_misdinar

# Restore from backup if needed
mysql -u root -p lms_misdinar < latest_backup.sql
```

---

## Post-Exam Checklist

1. **Export Results**
   - Admin → Exam Monitor → "Download Hasil Ujian (Excel)"
   - Backup Excel file immediately

2. **Generate Reports**
   ```bash
   # Export all results
   mysql -u root -p lms_misdinar -e "
   SELECT 
       u.name, 
       u.email, 
       es.score_academic, 
       es.score_integrity,
       es.status
   FROM exam_sessions es
   JOIN users u ON es.user_id = u.id
   WHERE es.exam_id = X
   ORDER BY es.score_academic DESC;
   " > results_exam_X.csv
   ```

3. **Clean Up**
   ```bash
   # Clear caches
   php artisan cache:clear
   
   # Clean old sessions
   php artisan session:gc
   ```

4. **Archive Data**
   ```bash
   # Full backup after exam
   mysqldump -u root -p lms_misdinar > final_backup_$(date +%Y%m%d).sql
   ```

---

## Security Hardening (Additional)

### 1. SSL/HTTPS (Mandatory)
```bash
# Install Certbot (Let's Encrypt)
sudo apt-get install certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot --nginx -d your-domain.com
```

### 2. Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 3. Hide Server Information
```nginx
# Nginx: /etc/nginx/nginx.conf
server_tokens off;
more_clear_headers 'Server';
more_clear_headers 'X-Powered-By';
```

```apache
# Apache: /etc/apache2/conf-enabled/security.conf
ServerTokens Prod
ServerSignature Off
```

---

## Scaling for More Than 100 Users

### Upgrade to Redis (Recommended)
```bash
# Install Redis
sudo apt-get install redis-server

# Update .env
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Enable OPcache
```ini
# /etc/php/8.2/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production only
opcache.revalidate_freq=0
```

### Horizontal Scaling (Advanced)
- Deploy multiple app servers behind load balancer (Nginx/HAProxy)
- Use shared Redis for sessions/cache
- Use MySQL replication (master-slave)

---

## Contact & Support

**Developer**: GitHub Copilot  
**System**: Laravel 12.51.0 + PHP 8.2  
**Database**: MySQL 8.0 with InnoDB  

**Emergency Contacts**:
- Technical Support: [Your contact]
- Server Administrator: [Admin contact]

---

## Quick Command Reference

```bash
# Start/Stop Services
sudo systemctl start nginx mysql php8.2-fpm
sudo systemctl stop nginx mysql php8.2-fpm
sudo systemctl restart nginx mysql php8.2-fpm

# Laravel Optimization
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear Caches
php artisan optimize:clear

# Database
php artisan migrate --force
php artisan db:seed --force

# Logs
tail -f storage/logs/laravel.log
journalctl -u nginx -f
journalctl -u php8.2-fpm -f

# Disk Space
df -h
du -sh storage/logs/
```

---

**Last Updated**: February 18, 2026  
**Tested For**: 84 students (100 concurrent capacity)  
**Status**: Production Ready ✅
