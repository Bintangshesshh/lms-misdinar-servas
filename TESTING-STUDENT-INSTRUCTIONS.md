# 🎓 Panduan Testing Ujian Misdinar - Untuk Siswa

## 📋 Informasi Login Kamu

**Pilih salah satu akun untuk login:**

| No | Email | Password | Nama |
|----|-------|----------|------|
| 1 | siswa1@test.com | password123 | Andreas Putra |
| 2 | siswa2@test.com | password123 | Benediktus Wijaya |
| 3 | siswa3@test.com | password123 | Christina Dewi |
| 4 | siswa4@test.com | password123 | Dominikus Santoso |
| 5 | siswa5@test.com | password123 | Elisabeth Putri |
| 6 | siswa6@test.com | password123 | Fransiskus Gunawan |
| 7 | siswa7@test.com | password123 | Gabriel Setiawan |
| 8 | siswa8@test.com | password123 | Helena Kusuma |
| 9 | siswa9@test.com | password123 | Ignatius Prasetyo |
| 10 | siswa10@test.com | password123 | Yohanes Budi |

---

## 🚀 Cara Ikut Testing Ujian (Step by Step)

### **STEP 1: Login ke Website** 🔐
1. Buka browser (Chrome/Firefox/Edge)
2. Ketik alamat: **http://localhost** (atau alamat yang diberikan admin)
3. Klik tombol **Login**
4. Masukkan:
   - **Email**: siswa(nomor)@test.com (contoh: siswa1@test.com)
   - **Password**: password123
5. Klik **Login**

✅ **Berhasil?** Kamu akan lihat halaman dashboard dengan daftar ujian.

---

### **STEP 2: Masuk ke Ruang Tunggu (Lobby)** ⏳
1. Di dashboard, cari ujian berjudul: **"Ujian Testing - Pengetahuan Umum"**
2. Status ujian harus: **"Lobby Terbuka"** (warna hijau)
3. Klik tombol **"Gabung"** atau **"Join"**
4. Tunggu di ruang tunggu sampai admin memulai ujian
5. Kamu akan lihat:
   - Countdown timer
   - Daftar siswa lain yang sudah join
   - Status: "Menunggu admin memulai ujian..."

⚠️ **JANGAN tutup browser atau pindah tab** - Tunggu sampai ujian dimulai!

---

### **STEP 3: Kerjakan Soal Ujian** ✍️
Setelah admin klik "Mulai Ujian", halaman otomatis pindah ke soal:

1. **Di pojok kanan atas**, ada TIMER countdown (15 menit)
2. **Baca soal dengan teliti**
3. **Pilih jawaban A/B/C/D** dengan klik salah satu option
4. **Klik tombol "Simpan Jawaban"** (warna hijau)
5. Tunggu notifikasi: "Jawaban tersimpan!" ✅
6. **Klik "Selanjutnya"** untuk soal berikutnya

📝 **Tips:**
- ✅ Soal yang sudah dijawab: nomor akan hijau
- ⚠️ Soal belum dijawab: nomor akan abu-abu
- Kamu bisa **pindah-pindah soal** menggunakan navigasi nomor soal
- **Jangan refresh halaman!** Jawaban bisa hilang
- **Jangan minimize browser!** Anti-cheat akan detect (integrity score turun)

---

### **STEP 4: Submit Setelah Selesai** 📤
Setelah mengerjakan semua soal:

1. Klik tombol **"Submit Ujian"** (warna biru, di bawah navigasi)
2. Akan muncul konfirmasi: "Apakah kamu yakin?"
3. Klik **OK/Ya**
4. Layar akan menampilkan **loading overlay** (tidak bisa klik apa-apa)
5. Tunggu sampai muncul halaman hasil

✅ **Selesai!** Kamu akan lihat:
- Skor kamu (0-100)
- Jumlah jawaban benar
- Integrity score (100% = tidak curang)

---

## ⚠️ PERHATIAN PENTING!

### ✅ **BOLEH:**
- Klik "Simpan Jawaban" berkali-kali (untuk update jawaban)
- Pindah-pindah soal menggunakan navigasi
- Baca soal pelan-pelan
- Ubah jawaban sebelum submit

### ❌ **JANGAN:**
- **Tutup browser** saat ujian (jawaban hilang!)
- **Pindah tab** (Google/YouTube/dll) → Integrity score turun! ⚠️
- **Minimize window** → System detect sebagai curang!
- **Screenshot** halaman ujian → Diblokir!
- **Refresh halaman** → Jawaban bisa hilang!
- **Klik tombol Back** browser → Keluar dari ujian!

---

## 🧪 TESTING YANG KAMI BUTUHKAN

Tolong bantu kami test dengan mencoba hal-hal ini:

### ✅ **Test Normal (Semua siswa):**
1. Login → Berhasil masuk?
2. Join lobby → Muncul di daftar?
3. Kerjakan 10 soal → Soal mudah dibaca? Timer jelas?
4. Submit → Loading overlay muncul? Hasil muncul?

### ⚠️ **Test Anti-Cheat (2-3 siswa):**
1. Coba buka **tab baru** (Google) saat ujian
2. Kembali ke ujian → Muncul warning? Integrity turun?
3. Coba **minimize window** saat ujian
4. Screenshot → Berhasil diblokir?

### 🚨 **Test Edge Cases (1-2 siswa):**
1. Jangan jawab soal, langsung submit → Bisa?
2. Biarkan timer habis 00:00 → Auto-submit?
3. Internet disconnect 10 detik → Reconnect masih bisa submit?

---

## 📝 FEEDBACK YANG KAMI BUTUHKAN

Setelah testing, tolong isi feedback:

### 1️⃣ **Login & Join (Easy/Sulit?)**
- Apakah login mudah dipahami?
- Apakah proses join lobby cepat?

### 2️⃣ **UI/UX Saat Ujian**
- Apakah soal mudah dibaca? (Font size cukup?)
- Apakah tombol mudah diklik? (Touchscreen friendly?)
- Apakah timer jelas terlihat?
- Apakah navigasi soal mudah dipahami?

### 3️⃣ **Performance**
- Apakah loading cepat? (<2 detik?)
- Apakah tombol response cepat saat diklik?
- Apakah ada lag/delay?

### 4️⃣ **Anti-Cheat**
- Apakah warning muncul saat pindah tab?
- Apakah screenshot diblokir?

### 5️⃣ **Submit & Result**
- Apakah submit overlay berfungsi? (Tidak bisa klik double?)
- Apakah hasil ujian muncul setelah submit?

---

## 🆘 TROUBLESHOOTING

### **Masalah: Tidak bisa login**
- ✅ Cek internet connection
- ✅ Cek email & password (copy-paste dari tabel)
- ✅ Refresh halaman (Ctrl + F5)

### **Masalah: Tombol "Gabung" tidak muncul**
- ✅ Tunggu admin membuka lobby
- ✅ Refresh halaman

### **Masalah: Jawaban tidak tersimpan**
- ✅ Cek internet connection
- ✅ Klik "Simpan Jawaban" lagi
- ✅ Tunggu notifikasi hijau muncul

### **Masalah: Submit tidak berfungsi**
- ✅ Pastikan sudah menjawab minimal 1 soal
- ✅ Refresh halaman, submit lagi

---

## 👨‍💼 UNTUK ADMIN/PENGAWAS

### **Cara Mulai Testing:**
1. Login sebagai **admin@test.com** / password123
2. Buka **Admin Dashboard**
3. Pilih ujian: **"Ujian Testing - Pengetahuan Umum"**
4. Klik **"Buka Lobby"** → Status berubah jadi "Lobby Terbuka"
5. Tunggu siswa join (lihat daftar "Students Joined: X/10")
6. Setelah semua join, klik **"Mulai Ujian"**
7. Monitor real-time:
   - Integrity score masing-masing siswa
   - Berapa soal yang sudah dikerjakan
   - Tab switching violations
8. Setelah 15 menit atau semua submit, klik **"Export to Excel"**

### **Monitor Checklist:**
- ✅ Loading time < 2 detik saat join?
- ✅ Real-time updates berfungsi?
- ✅ Anti-cheat detection working? (integrity drop?)
- ✅ No server errors di console (F12)?
- ✅ Excel export berhasil?

---

## ✅ TESTING CHECKLIST

Print checklist ini dan centang saat testing:

```
BEFORE TESTING:
[ ] Internet connection stabil
[ ] Browser updated (Chrome/Firefox/Edge)
[ ] 10 siswa sudah dapat login credentials
[ ] Admin sudah login & siap buka lobby

DURING TESTING:
[ ] Semua siswa berhasil login (10/10)
[ ] Semua siswa berhasil join lobby (10/10)
[ ] Exam started successfully
[ ] Timer countdown berfungsi
[ ] Soal mudah dibaca
[ ] Tombol response cepat (<500ms)
[ ] Simpan jawaban berfungsi
[ ] Anti-cheat warning muncul saat pindah tab
[ ] No server lag/crash
[ ] Submit overlay berfungsi (tidak bisa double submit)
[ ] Hasil ujian muncul setelah submit

AFTER TESTING:
[ ] Excel export berhasil
[ ] Integrity scores tercatat
[ ] Feedback dikumpulkan dari siswa
[ ] Admin monitoring dashboard lengkap
[ ] No critical bugs found
```

---

**🚀 Terima kasih sudah membantu testing! Feedback kalian sangat penting untuk ujian sesungguhnya nanti!**

**📞 Kontak:** Jika ada error/bug, screenshot dan laporkan ke admin.

