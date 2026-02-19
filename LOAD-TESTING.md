# Load Testing Guide - LMS Misdinar

## Prerequisites

Install load testing tools:

```bash
# Option 1: Apache Bench (Simple, built-in)
# Already available with Apache, or install:
sudo apt-get install apache2-utils  # Ubuntu/Debian
brew install ab                      # macOS

# Option 2: Artillery (Advanced, recommended)
npm install -g artillery

# Option 3: K6 (Professional, by Grafana)
brew install k6                      # macOS
# Or download from https://k6.io/docs/getting-started/installation/
```

---

## Method 1: Apache Bench (Quick Test)

### Basic Load Test
```bash
# Test homepage (100 concurrent users, 1000 total requests)
ab -n 1000 -c 100 http://localhost/

# Test with timeout (prevent hanging)
ab -n 1000 -c 100 -t 30 http://localhost/

# Test login page
ab -n 500 -c 50 -p login-data.txt -T "application/x-www-form-urlencoded" http://localhost/login

# Test with cookies (authenticated)
ab -n 1000 -c 100 -C "laravel_session=YOUR_SESSION_COOKIE" http://localhost/student/dashboard
```

### Create login-data.txt
```
email=student@test.com&password=password&_token=CSRF_TOKEN
```

### Interpret Results
```
Requests per second:    250 [#/sec] (mean)    ← Higher is better
Time per request:       4.0 [ms] (mean)       ← Lower is better
Failed requests:        0                      ← Should be 0
```

**Success Criteria:**
- ✅ Requests/sec > 100
- ✅ Time/request < 500ms
- ✅ Failed requests = 0

---

## Method 2: Artillery (Realistic Scenarios)

### Install Artillery
```bash
npm install -g artillery
artillery --version
```

### Test 1: Homepage Load Test
Create `test-homepage.yml`:
```yaml
config:
  target: 'http://localhost'
  phases:
    - duration: 60
      arrivalRate: 10
      name: "Warm up"
    - duration: 120
      arrivalRate: 50
      name: "Sustained load - 50 users/sec"
    - duration: 60
      arrivalRate: 100
      name: "Peak load - 100 users/sec"

scenarios:
  - name: "Visit Homepage"
    flow:
      - get:
          url: "/"
```

Run:
```bash
artillery run test-homepage.yml
```

### Test 2: Student Login & Dashboard Flow
Create `test-student-flow.yml`:
```yaml
config:
  target: 'http://localhost'
  phases:
    - duration: 180
      arrivalRate: 50
      name: "50 concurrent students"
  defaults:
    headers:
      Content-Type: "application/json"
      Accept: "application/json"

scenarios:
  - name: "Student Exam Flow"
    flow:
      # Login
      - get:
          url: "/login"
          capture:
            - xpath: "//input[@name='_token']/@value"
              as: "csrf_token"
      
      - post:
          url: "/login"
          json:
            email: "student{{ $randomNumber(1, 84) }}@test.com"
            password: "password"
            _token: "{{ csrf_token }}"
      
      # Dashboard
      - get:
          url: "/student/dashboard"
          expect:
            - statusCode: 200
      
      # Think time (simulate user reading)
      - think: 5
      
      # Join lobby
      - post:
          url: "/student/exam/1/join"
      
      # Wait in lobby
      - think: 10
      
      # Take exam (if started)
      - get:
          url: "/student/exam/1/take"
      
      # Answer questions
      - loop:
          - post:
              url: "/student/exam/1/save-answer"
              json:
                question_id: "{{ $randomNumber(1, 20) }}"
                selected_answer: "{{ $pick('a', 'b', 'c', 'd') }}"
          - think: 3
        count: 20
      
      # Submit exam
      - post:
          url: "/student/exam/1/submit"
```

Run:
```bash
artillery run test-student-flow.yml
```

### Test 3: Admin Monitoring (Heavy Polling)
Create `test-admin-polling.yml`:
```yaml
config:
  target: 'http://localhost'
  phases:
    - duration: 300
      arrivalRate: 5
      name: "5 admins polling every 2 seconds"

scenarios:
  - name: "Admin Monitor Polling"
    flow:
      # Login as admin
      - get:
          url: "/login"
      
      - post:
          url: "/login"
          json:
            email: "admin@test.com"
            password: "password"
      
      # Poll lobby status repeatedly
      - loop:
          - get:
              url: "/admin/exam/1/lobby-status"
              expect:
                - statusCode: 200
                - contentType: json
          - think: 2
        count: 150
```

Run:
```bash
artillery run test-admin-polling.yml
```

### Artillery Report Interpretation
```
Summary report @ 14:23:45
  Scenarios launched:  5000
  Scenarios completed: 5000
  Requests completed:  15000
  Mean response/sec: 250
  Response time (msec):
    min: 12
    max: 850
    median: 45          ← Most important
    p95: 120            ← 95% under this time
    p99: 250            ← Should be < 500ms
  Scenario duration (msec):
    min: 1200
    max: 15000
    median: 3500
    p95: 7000
    p99: 10000
  Codes:
    200: 14850          ← Success
    500: 150            ← Should be 0!
```

**Success Criteria:**
- ✅ p95 response time < 500ms
- ✅ p99 response time < 1000ms
- ✅ Error rate < 0.1%
- ✅ Scenarios completed = launched

---

## Method 3: Manual Testing (Before Load Test)

### Prerequisites
```bash
# Create test users
php artisan tinker
```

```php
// In tinker:
// Create 84 students
for ($i = 1; $i <= 84; $i++) {
    User::create([
        'name' => "Student $i",
        'email' => "student$i@test.com",
        'password' => bcrypt('password'),
        'role' => 'student',
    ]);
}

// Create 5 admins
for ($i = 1; $i <= 5; $i++) {
    User::create([
        'name' => "Admin $i",
        'email' => "admin$i@test.com",
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);
}

// Create test exam with 20 questions
$exam = Exam::create([
    'title' => 'Load Test Exam',
    'description' => 'For testing 100 concurrent users',
    'duration_minutes' => 60,
    'is_active' => true,
    'status' => 'lobby',
    'mata_pelajaran' => 'General',
]);

for ($i = 1; $i <= 20; $i++) {
    Question::create([
        'exam_id' => $exam->id,
        'question' => "Question $i: What is the answer?",
        'answer_a' => 'Option A',
        'answer_b' => 'Option B',
        'answer_c' => 'Option C',
        'answer_d' => 'Option D',
        'correct_answer' => ['a', 'b', 'c', 'd'][rand(0, 3)],
        'points' => 5,
        'order' => $i,
    ]);
}

exit
```

### Manual Simulation Steps
1. **Open 10+ browser tabs** (incognito mode)
2. Login as different students (student1@test.com to student10@test.com)
3. All join lobby simultaneously
4. Admin starts exam
5. All students answer questions rapidly
6. Monitor server resources:
   ```bash
   htop
   watch -n 1 'mysql -u root -p -e "SHOW PROCESSLIST;" | wc -l'
   ```

---

## Monitoring During Load Test

### Terminal 1: Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep -E "ERROR|CRITICAL|exception"
```

### Terminal 2: System Resources
```bash
# Linux
htop

# Or use top with sorting
top -o %CPU

# Memory specifically
watch -n 1 free -h
```

### Terminal 3: MySQL Connections
```bash
# Count active connections
watch -n 1 'mysql -u root -e "SHOW PROCESSLIST;" | wc -l'

# See what queries are running
mysql -u root -p
```
```sql
SHOW FULL PROCESSLIST;
SHOW STATUS LIKE 'Threads_connected';
SHOW STATUS LIKE 'Max_used_connections';
```

### Terminal 4: Network Traffic
```bash
# Monitor network bytes
iftop

# Or simpler:
watch -n 1 'netstat -an | grep :80 | wc -l'
```

### Terminal 5: Response Time Tracking
```bash
# Ping application every second
while true; do
  curl -o /dev/null -s -w "Time: %{time_total}s\n" http://localhost/
  sleep 1
done
```

---

## Results Analysis

### Query Performance
```sql
-- Find slow queries (after enabling slow query log)
SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;

-- Most hit tables
SELECT TABLE_NAME, TABLE_ROWS, 
       ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size_MB'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'lms_misdinar'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- Check index usage
SHOW INDEX FROM exam_sessions;
EXPLAIN SELECT * FROM exam_sessions WHERE exam_id = 1 AND status = 'ongoing';
```

### Laravel Performance
```bash
# Enable query logging in AppServiceProvider
DB::listen(function($query) {
    if ($query->time > 100) { // Queries taking > 100ms
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
            'bindings' => $query->bindings,
        ]);
    }
});
```

---

## Optimization Based on Results

### If Response Time > 500ms
1. **Add more caching**:
   ```php
   Cache::remember('key', 300, fn() => /* query */);
   ```

2. **Add database indexes**:
   ```sql
   CREATE INDEX idx_name ON table_name(column_name);
   ```

3. **Enable Redis** (instead of database cache):
   ```env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   ```

### If High CPU Usage
1. **Enable OPcache** (PHP 8.2):
   ```ini
   opcache.enable=1
   opcache.memory_consumption=256
   ```

2. **Optimize images/assets**:
   ```bash
   npm run build
   php artisan optimize
   ```

### If High Memory Usage
1. **Chunk large queries**:
   ```php
   Exam::chunk(100, function ($exams) {
       // Process in batches
   });
   ```

2. **Increase PHP memory limit**:
   ```ini
   memory_limit = 512M
   ```

### If Too Many MySQL Connections
1. **Reduce connection pool**:
   ```env
   DB_CONNECTION_POOL_SIZE=20
   ```

2. **Use persistent connections**:
   ```env
   DB_PERSISTENT=true
   ```

3. **Increase MySQL max_connections**:
   ```sql
   SET GLOBAL max_connections = 300;
   ```

---

## Load Test Scenarios

### Scenario 1: Normal Load (Baseline)
- **Duration**: 5 minutes
- **Concurrent Users**: 50
- **Expected**: All green, < 200ms response time

### Scenario 2: Peak Load (Exam Start)
- **Duration**: 2 minutes
- **Concurrent Users**: 100 (all students join at once)
- **Expected**: < 500ms response time, no errors

### Scenario 3: Sustained Load (During Exam)
- **Duration**: 30 minutes
- **Concurrent Users**: 84 (all students + 5 admins)
- **Expected**: Stable performance, no memory leaks

### Scenario 4: Heavy Polling (Admin Monitoring)
- **Duration**: 10 minutes
- **Polling**: 5 admins × 1 request per 2 seconds = 2.5 req/sec
- **Expected**: Cache hits, minimal DB queries

### Scenario 5: Spike Test (Stress Test)
- **Duration**: 1 minute
- **Concurrent Users**: 200 (over capacity)
- **Expected**: Graceful degradation, rate limiting kicks in

---

## Quick Reference Commands

```bash
# Apache Bench: Simple test
ab -n 1000 -c 100 http://localhost/

# Artillery: Full scenario
artillery run test-student-flow.yml

# Monitor everything
watch -n 1 'echo "=== Connections ==="; mysql -u root -e "SHOW STATUS LIKE \"Threads_connected\""; echo "=== Memory ==="; free -h; echo "=== CPU ==="; top -bn1 | grep "Cpu(s)"'

# Check Laravel cache hit rate
php artisan cache:table
mysql -u root -p lms_misdinar -e "SELECT COUNT(*) FROM cache;"

# Clear everything and re-test
php artisan optimize:clear && php artisan optimize
```

---

## Success Checklist

Before declaring "Production Ready":

- [ ] Homepage loads in < 200ms (50 concurrent)
- [ ] Login works with 100 simultaneous requests
- [ ] Dashboard loads in < 300ms (84 concurrent students)
- [ ] Exam join handles 84 students in < 5 seconds
- [ ] Answer auto-save works with 120 req/min per student
- [ ] Admin polling (every 2s) doesn't slow down student views
- [ ] Exam submit handles 84 submissions in < 10 seconds
- [ ] No 500 errors during entire test
- [ ] MySQL connections stay < 150
- [ ] CPU usage < 80% during peak
- [ ] Memory usage < 80% during sustained load
- [ ] No memory leaks (check after 1 hour continuous load)

---

**Test Date**: _________  
**Tester**: _________  
**Result**: ⬜ PASS / ⬜ FAIL  
**Notes**: _________________________________________
