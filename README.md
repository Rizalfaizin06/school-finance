# Sistem Keuangan Sekolah (SD) – MVP

Sistem Keuangan Sekolah adalah aplikasi berbasis web untuk membantu pengelolaan keuangan Sekolah Dasar (SD), khususnya dalam pencatatan pembayaran SPP, pengeluaran operasional, serta penyajian laporan keuangan sederhana.

Aplikasi ini dikembangkan sebagai **Minimum Viable Product (MVP)** menggunakan **Laravel 12** dan **Filament Admin Panel**, dengan fokus pada kemudahan penggunaan oleh bendahara sekolah dan transparansi bagi kepala sekolah.

---

## 🎯 Tujuan Sistem

-   Mencatat pembayaran SPP siswa secara terstruktur
-   Mencatat pengeluaran sekolah
-   Mengetahui saldo kas sekolah secara real-time
-   Menyediakan laporan keuangan sederhana
-   Mengurangi pencatatan manual (buku kas / Excel)

---

## 🧩 Ruang Lingkup (MVP)

### ✅ Fitur Utama

-   Manajemen data siswa
-   Manajemen data kelas
-   Pencatatan pembayaran SPP
-   Pencatatan pengeluaran sekolah
-   Dashboard ringkasan keuangan
-   Laporan kas masuk & keluar
-   Manajemen pengguna & role

### ❌ Di Luar MVP

-   Akuntansi jurnal & buku besar
-   Pengelolaan Dana BOS detail
-   Notifikasi WhatsApp / Email
-   Multi sekolah
-   Approval transaksi berlapis

---

## 🏗️ Teknologi yang Digunakan

-   **Framework**: Laravel 12
-   **Admin Panel**: Filament v3
-   **Database**: MySQL / MariaDB
-   **Authentication**: Laravel + Filament
-   **Authorization**: Role & Permission
-   **Export Data**: Excel

---

## 👤 Role Pengguna

-   **Admin**
    -   Mengelola master data
    -   Mengelola pengguna
-   **Bendahara**
    -   Mencatat pembayaran SPP
    -   Mencatat pengeluaran
    -   Melihat laporan
-   **Kepala Sekolah**
    -   Melihat dashboard & laporan (read-only)

---

## 📂 Struktur Menu (MVP)

-   Dashboard
-   Data Kelas
-   Data Siswa
-   Pembayaran SPP
-   Pengeluaran
-   Laporan Keuangan
-   Pengguna & Hak Akses

---

## 💰 Konsep Keuangan

-   **Kas** adalah tempat uang berada (Tunai / Bank)
-   **SPP** adalah sumber pemasukan
-   Semua pemasukan masuk ke kas
-   Semua pengeluaran keluar dari kas
-   Saldo kas dihitung otomatis dari transaksi

---

## 🔁 Alur Singkat Sistem

1. Admin menginput data kelas & siswa
2. Bendahara mencatat pembayaran SPP siswa
3. Bendahara mencatat pengeluaran sekolah
4. Sistem menghitung saldo kas otomatis
5. Kepala sekolah memantau laporan keuangan

---

## ⚙️ Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Rizalfaizin06/school-finance.git
cd sistem-keuangan-sekolah
```

### 2. Install Dependency

```bash
composer install
npm install
npm run build
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Atur konfigurasi database pada file `.env`.

### 4. Migrasi & Seeder

```bash
php artisan migrate --seed
```

### 5. Install Filament

```bash
php artisan filament:install --panels
```

### 6. Jalankan Aplikasi

```bash
php artisan serve
```

Akses admin panel:

```
http://localhost:8000/admin
```

---

## 🧪 Akun Default (Seeder)

| Role           | Email                                                                               | Password |
| -------------- | ----------------------------------------------------------------------------------- | -------- |
| Admin          | [admin.edu@rizalscompanylab.my.id](mailto:admin.edu@rizalscompanylab.my.id)         | password |
| Bendahara      | [bendahara.edu@rizalscompanylab.my.id](mailto:bendahara.edu@rizalscompanylab.my.id) | password |
| Kepala Sekolah | [kepsek.edu@rizalscompanylab.my.id](mailto:kepsek.edu@rizalscompanylab.my.id)       | password |

---

## 📊 Laporan

Laporan keuangan dapat difilter berdasarkan periode tertentu dan diexport ke format Excel untuk kebutuhan pelaporan internal sekolah.

---

## 📌 Catatan Pengembangan

Aplikasi ini dirancang modular agar mudah dikembangkan ke versi lanjutan, seperti:

-   Pengelolaan Dana BOS
-   Multi tahun ajaran
-   Approval transaksi
-   Integrasi pembayaran digital

---

## 📝 Lisensi

Proyek ini dikembangkan untuk keperluan edukasi, penelitian, dan penggunaan internal sekolah.

---

## 👨‍💻 Pengembang

**Rizal Faizin Firdaus**
Web Developer
Laravel • Filament • Backend System
