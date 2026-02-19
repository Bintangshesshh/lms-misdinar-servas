# Troubleshooting Anti-Cheat di Incognito Mode

## Issue: Anti-cheat tidak berfungsi di mode incognito

Jika Anda menemukan bahwa fitur anti-cheat (tab switching detection, integrity scoring) tidak berfungsi saat menggunakan browser incognito/private mode, ikuti panduan troubleshooting ini.

---

## 🔍 Step 1: Check Browser Console (PALING PENTING!)

### Cara Buka Console:
1. **Buka halaman exam** di incognito mode
2. **Tekan F12** atau **Ctrl+Shift+I** (Windows) / **Cmd+Option+I** (Mac)
3. **Klik tab "Console"**
4. **Switch ke tab lain** (untuk trigger anti-cheat)
5. **Kembali ke tab exam**

### Cek Output di Console:

#### ✅ **Expected Output (Working):**
```
[Anti-Cheat] Reporting violation: tab_switch Duration: 3.5 Session: 123
[Anti-Cheat] CSRF Token: Present
[Anti-Cheat] URL: /student/integrity/log-violation
[Anti-Cheat] Response status: 200 OK
[Anti-Cheat] Success: {current_integrity: 70, penalty_applied: 30, terminated: false}
```

#### ❌ **Error Output (NOT Working):**

**Scenario A: CSRF Token Missing**
```
[Anti-Cheat] Reporting violation: tab_switch Duration: 3.5 Session: 123
[Anti-Cheat] CSRF Token: MISSING!  ← MASALAH DI SINI!
[Anti-Cheat] URL: /student/integrity/log-violation
[Anti-Cheat] Response status: 419 Page Expired
[Anti-Cheat] Error response: CSRF token mismatch
```

**Fix untuk Scenario A:**
1. Refresh halaman exam (Ctrl+R atau F5)
2. Clear browser cache: Settings → Privacy → Clear browsing data
3. Restart browser dan login ulang

---

**Scenario B: Authentication Error (401/403)**
```
[Anti-Cheat] Response status: 401 Unauthorized
[Anti-Cheat] Error response: Unauthenticated.
```

**Fix untuk Scenario B:**
1. Incognito mode mungkin block cookies → Check browser settings
2. Login ulang di incognito mode
3. Pastikan session cookie terkirim (lihat Step 2 di bawah)

---

**Scenario C: Network Error**
```
[Anti-Cheat] Failed to report violation: TypeError: Failed to fetch
[Anti-Cheat] Error details: {type: "tab_switch", duration: 3.5, ...}
```

**Fix untuk Scenario C:**
1. Check koneksi internet
2. Check browser extension yang block requests (AdBlock, Privacy Badger)
3. Disable strict tracking prevention di browser settings

---

## 🍪 Step 2: Check Cookies & Session

### Chrome/Edge:
1. F12 → Tab **Application** → Sidebar **Cookies** → `http://localhost`
2. Cari cookie bernama: **`laravel_session`** atau **`XSRF-TOKEN`**
3. Pastikan cookies ada dan tidak expired

### Firefox:
1. F12 → Tab **Storage** → **Cookies** → `http://localhost`
2. Cari cookie **`laravel_session`**

### ✅ Expected:
```
Name: laravel_session
Value: eyJpdiI6... (long string)
Domain: localhost
HttpOnly: ✓
Secure: (depends on HTTPS)
SameSite: Lax
```

### ❌ If Cookie Missing:
Incognito mode might have blocked cookies. Check:

**Chrome:**
- Settings → Privacy and security → Cookies and other site data
- Set to: **"Allow all cookies"** (sementara untuk testing)
- Jangan pilih "Block all cookies" atau "Block third-party cookies in Incognito"

**Firefox:**
- Settings → Privacy & Security → Cookies and Site Data
- Set to: **"Standard"** (not Strict)

---

## 🔬 Step 3: Manual Test Script

Buka browser console (F12 → Console) dan paste script ini:

```javascript
// Test 1: Check variables
console.log('=== Anti-Cheat Diagnostic ===');
console.log('SESSION_ID:', typeof SESSION_ID !== 'undefined' ? SESSION_ID : 'UNDEFINED!');
console.log('CSRF_TOKEN:', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : 'UNDEFINED!');
console.log('EXAM_ID:', typeof EXAM_ID !== 'undefined' ? EXAM_ID : 'UNDEFINED!');

// Test 2: Check DOM element
var dataEl = document.getElementById('exam-data');
if (dataEl) {
    console.log('Data Element: Found');
    console.log('- Session ID:', dataEl.dataset.sessionId);
    console.log('- CSRF:', dataEl.dataset.csrf ? 'Present' : 'MISSING!');
} else {
    console.error('Data Element: NOT FOUND!');
}

// Test 3: Manual fetch test
if (typeof SESSION_ID !== 'undefined' && typeof CSRF_TOKEN !== 'undefined') {
    console.log('Testing manual violation report...');
    fetch('/student/integrity/log-violation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            session_id: SESSION_ID,
            type: 'tab_switch',
            duration: 5
        })
    })
    .then(res => {
        console.log('Response Status:', res.status, res.statusText);
        return res.json();
    })
    .then(data => {
        console.log('Success Response:', data);
        if (data.current_integrity) {
            console.log('✅ Anti-cheat is WORKING!');
            console.log('New Integrity:', data.current_integrity);
        }
    })
    .catch(err => {
        console.error('❌ Request Failed:', err);
    });
} else {
    console.error('Cannot test: Variables not initialized!');
}
```

### Expected Output:
```
=== Anti-Cheat Diagnostic ===
SESSION_ID: 123
CSRF_TOKEN: aB3dE...fGh (long token)
EXAM_ID: 5
Data Element: Found
- Session ID: 123
- CSRF: Present
Testing manual violation report...
Response Status: 200 OK
Success Response: {current_integrity: 70, penalty_applied: 30, ...}
✅ Anti-cheat is WORKING!
New Integrity: 70
```

---

## 🛠️ Step 4: Backend Verification

### Check Laravel Logs:
```bash
# Windows PowerShell
Get-Content d:\laragon\www\LMS-Misdinar\storage\logs\laravel.log -Tail 50

# Cari error seperti:
# - "CSRF token mismatch"
# - "Unauthenticated"
# - "Session expired"
```

### Check Route:
```bash
php artisan route:list | Select-String "integrity"
```

Expected:
```
POST   student/integrity/log-violation ... integrity.logViolation
GET    student/integrity/status/{session_id} ... integrity.status
```

### Test Route Directly (Postman/Insomnia):
```http
POST http://localhost/student/integrity/log-violation
Content-Type: application/json
X-CSRF-TOKEN: YOUR_CSRF_TOKEN
Cookie: laravel_session=YOUR_SESSION_COOKIE

{
  "session_id": 123,
  "type": "tab_switch",
  "duration": 5
}
```

Expected Response:
```json
{
  "status": "logged",
  "penalty_applied": 30,
  "current_integrity": 70,
  "terminated": false
}
```

---

## 🔧 Common Fixes

### Fix 1: Clear All Caches
```bash
# Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Rebuild frontend
npm run build
```

### Fix 2: Session Configuration
Check `.env`:
```env
SESSION_DRIVER=database    # atau 'file' (default)
SESSION_LIFETIME=120       # 2 hours
SESSION_SECURE_COOKIE=false  # true if HTTPS
SESSION_SAME_SITE=lax      # or 'strict'
```

### Fix 3: CORS Configuration (If using different domain)
Edit `config/cors.php`:
```php
'supports_credentials' => true,
```

### Fix 4: Browser Compatibility

**Test di browser lain:**
- Chrome Incognito → Test
- Firefox Private Window → Test
- Edge InPrivate → Test

Jika works di satu browser tapi tidak di browser lain:
- Browser extension interfering (disable semua extensions)
- Browser settings blocking cookies/localStorage
- Browser outdated (update ke versi terbaru)

---

## 🧪 Step 5: Simplified Test (Tanpa Incognito)

**Test dulu di regular mode (bukan incognito):**

1. **Open regular browser** (bukan incognito)
2. **Login** sebagai student
3. **Join exam** dan take exam
4. **Switch tab** 3x
5. **Check integrity score** turun dari 100 → 70 → 40 → 10

### ✅ Jika works di regular mode tapi NOT di incognito:
**Root Cause**: Browser settings di incognito mode

**Fix:**
1. **Chrome**: `chrome://settings/content/cookiesIncognito`
   - Set: **"Allow all cookies"**
   
2. **Firefox**: Settings → Privacy → Custom
   - Uncheck **"Delete cookies when Firefox is closed"**
   
3. **Edge**: Settings → Privacy → Cookies
   - Set: **"Allow all cookies"**

---

## 🚨 Emergency Workaround (If Nothing Works)

Jika anti-cheat tetap tidak works di incognito setelah semua troubleshooting:

### Temporary Fix: Disable Anti-Cheat di Incognito

**Option 1**: Detect incognito dan skip anti-cheat
```javascript
// Add to exam-take.blade.php
function isIncognito() {
    return new Promise(function(resolve) {
        // Chrome/Edge detection
        var fs = window.RequestFileSystem || window.webkitRequestFileSystem;
        if (!fs) {
            resolve(false); // Firefox tidak support detection ini
        } else {
            fs(window.TEMPORARY, 100, function() {
                resolve(false); // Not incognito
            }, function() {
                resolve(true); // Is incognito
            });
        }
    });
}

// Before initializing anti-cheat:
isIncognito().then(function(incognito) {
    if (incognito) {
        console.warn('Incognito mode detected: Anti-cheat disabled');
        return; // Skip anti-cheat setup
    }
    // ... normal anti-cheat initialization
});
```

**Option 2**: Force students to use regular mode
Add warning banner:
```html
<div class="bg-red-600 text-white p-4 text-center">
    ⚠️ PERHATIAN: Jangan gunakan mode incognito/private untuk ujian!
    Anti-cheat monitoring tidak akan berfungsi dengan benar.
</div>
```

---

## 📊 Debugging Checklist

Before reporting issue, verify:

- [ ] Console shows `[Anti-Cheat]` logs
- [ ] CSRF token is present (not "MISSING!")
- [ ] Session cookie exists dan tidak expired
- [ ] Response status is 200 (not 401/403/419)
- [ ] Tested in regular mode (works?)
- [ ] Tested in different browser (works?)
- [ ] Laravel logs show no errors
- [ ] Route `/student/integrity/log-violation` exists
- [ ] Student is authenticated (logged in)
- [ ] Exam session status is "ongoing" (not "completed" or "blocked")

---

## 🆘 Still Not Working?

Jika sudah mengikuti semua langkah dan masih tidak works:

### Collect Debug Information:

1. **Browser Info:**
   - Browser name & version: _________________
   - Incognito/Private mode: Yes/No
   - Extensions installed: _________________

2. **Console Output:**
   ```
   [Paste console output di sini]
   ```

3. **Network Tab (F12 → Network):**
   - Find request: `/student/integrity/log-violation`
   - Status code: _________________
   - Response: _________________
   - Request headers: [Screenshot]

4. **Cookies (F12 → Application → Cookies):**
   - `laravel_session`: Present/Missing
   - `XSRF-TOKEN`: Present/Missing

5. **Laravel Log:**
   ```
   [Paste last 20 lines dari storage/logs/laravel.log]
   ```

Dengan informasi ini, developer bisa diagnose issue lebih akurat.

---

## ✅ Expected Behavior (Reference)

### Normal Flow:
1. Student login → Session cookie created
2. Join exam → CSRF token embedded in page
3. Take exam → JavaScript initialized
4. Switch tab → `visibilitychange` event fired
5. Grace period (3 seconds) → If still away, trigger violation
6. `reportViolation()` called → Fetch POST to `/student/integrity/log-violation`
7. Backend validates → Deduct 30 points → Return new score
8. Frontend updates → Integrity bar changes, warning shown
9. If score = 0 → Auto-terminate (show terminated overlay)

### Timeline Example:
```
00:00 - Exam started, integrity = 100%
00:15 - Student switch tab (trigger)
00:18 - Still away after 3s → Report violation
00:18 - Backend response: integrity = 70% (−30)
00:18 - Frontend updates bar (100% → 70%)
00:18 - Warning banner shows 5 seconds
00:30 - Student switch tab again
00:33 - Report violation #2
00:33 - Backend response: integrity = 40% (−30)
00:33 - Frontend updates bar (70% → 40%)
01:00 - Student switch tab 3rd time
01:03 - Report violation #3
01:03 - Backend response: integrity = 10% (−30)
01:15 - Student switch tab 4th time
01:18 - Report violation #4
01:18 - Backend response: integrity = 0%, terminated = true
01:18 - Frontend shows terminated overlay (fullscreen block)
```

---

**Last Updated**: February 18, 2026  
**System**: Laravel 12.51.0 + JavaScript Anti-Cheat  
**Version**: 1.0
