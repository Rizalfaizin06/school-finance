# Sistem Keuangan Sekolah Dasar (SD)

Sistem Manajemen Keuangan berbasis web untuk Sekolah Dasar menggunakan Laravel 12 dan Filament 4.

## 🎯 Fitur Utama

### 📊 Dashboard

-   **Saldo Kas Saat Ini** - Total saldo dari semua akun kas
-   **Pemasukan Bulan Ini** - Total pembayaran SPP & non-SPP
-   **Pengeluaran Bulan Ini** - Total pengeluaran operasional
-   **Siswa Belum Bayar SPP** - Monitoring tunggakan
-   **Grafik Keuangan** - Visualisasi pemasukan vs pengeluaran 6 bulan terakhir
-   **Tabel Pembayaran Terbaru** - 10 transaksi terakhir

### 📚 Master Data

1. **Data Siswa** - Manajemen data siswa lengkap dengan NIS, NISN, kelas, orang tua
2. **Data Kelas** - Manajemen kelas per tahun ajaran
3. **Tahun Ajaran** - Pengelolaan tahun ajaran aktif
4. **Jenis Pembayaran** - SPP, Uang Kegiatan, Seragam, Dana BOS
5. **Kategori Pengeluaran** - Gaji, ATK, Perawatan, Utilitas, dll
6. **Akun Kas** - Kas Tunai, Bank BRI, Bank Mandiri

### 💰 Transaksi

1. **Pembayaran (SPP & Non-SPP)**

    - Input pembayaran siswa
    - Auto-generate nomor kwitansi (KWT/YYYYMMDD/0001)
    - Pilih metode pembayaran (Cash/Transfer/Check)
    - Cetak kwitansi (akan ditambahkan)

2. **Pengeluaran**
    - Input pengeluaran sekolah
    - Auto-generate nomor pengeluaran (EXP/YYYYMMDD/0001)
    - Upload bukti nota/kwitansi
    - Approval oleh Kepala Sekolah

### 👥 Manajemen Pengguna & Roles

**3 Role Default:**

1. **Admin** - Full access ke semua fitur
2. **Bendahara** - Input transaksi, cetak laporan, lihat dashboard
3. **Kepala Sekolah** - View only + approve pengeluaran

## 🔐 Akun Default

```
Admin:
Email: admin@school.com
Password: password

Bendahara:
Email: bendahara@school.com
Password: password

Kepala Sekolah:
Email: kepsek@school.com
Password: password
```

## 🚀 Instalasi

### Prerequisites

-   PHP 8.2+
-   MySQL/MariaDB
-   Composer
-   Node.js & NPM

### Langkah Instalasi

1. **Clone/Download Project**

```bash
cd school-finance
```

2. **Install Dependencies**

```bash
composer install
npm install
```

3. **Setup Environment**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Konfigurasi Database**
   Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_finance
DB_USERNAME=root
DB_PASSWORD=
```

5. **Migrate & Seed Database**

```bash
php artisan migrate:fresh --seed
```

6. **Build Assets**

```bash
npm run build
```

7. **Jalankan Server**

```bash
php artisan serve
```

8. **Akses Aplikasi**

-   URL: http://localhost:8000/admin
-   Login dengan salah satu akun default di atas

## 📁 Struktur Database

### Master Data Tables

-   `academic_years` - Tahun ajaran
-   `classes` - Data kelas
-   `students` - Data siswa
-   `fee_types` - Jenis pembayaran
-   `expense_categories` - Kategori pengeluaran
-   `accounts` - Akun kas/bank
-   `school_profiles` - Profil sekolah

### Transaction Tables

-   `payments` - Transaksi pembayaran siswa
-   `expenses` - Transaksi pengeluaran

### Permission Tables (Spatie)

-   `roles` - Role pengguna
-   `permissions` - Permission detail
-   `role_has_permissions` - Mapping role-permission
-   `model_has_roles` - Mapping user-role

## 🛠️ Tech Stack

-   **Backend**: Laravel 12
-   **Admin Panel**: Filament 4.0
-   **Database**: MySQL
-   **Authentication**: Laravel Breeze + Spatie Permission
-   **PDF Generation**: Laravel DomPDF
-   **Icons**: Heroicons

## 📝 Development Roadmap

### ✅ Phase 1 (Completed)

-   [x] Database schema & migrations
-   [x] Eloquent models dengan relationships
-   [x] Role & permissions setup
-   [x] Filament resources untuk Master Data
-   [x] Filament resources untuk Transaksi
-   [x] Dashboard widgets (stats, chart, recent payments)

### 🔄 Phase 2 (Next)

-   [ ] Custom laporan pages dengan filter
-   [ ] Export laporan ke Excel & PDF
-   [ ] Cetak kwitansi pembayaran
-   [ ] Laporan SPP per kelas
-   [ ] Laporan tunggakan siswa
-   [ ] Bulk import siswa dari Excel
-   [ ] Email/SMS notifikasi tunggakan

### 🔮 Phase 3 (Future)

-   [ ] Portal orang tua (view tagihan online)
-   [ ] Integrasi payment gateway
-   [ ] Multi-year archive
-   [ ] Auto backup database
-   [ ] Activity log detail
-   [ ] 2FA untuk admin

## 🎨 Customization

### Ubah Logo & Profil Sekolah

Login sebagai Admin → Master Data → School Profile

### Tambah Jenis Pembayaran Baru

Master Data → Jenis Pembayaran → Create

### Tambah Kategori Pengeluaran

Master Data → Kategori Pengeluaran → Create

### Manage Permissions

Customize permissions di:
`database/seeders/RolePermissionSeeder.php`

## 🐛 Troubleshooting

**Error: "Class 'DomPDF' not found"**

```bash
composer require barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

**Error: "SQLSTATE[42S02]: Base table or view not found"**

```bash
php artisan migrate:fresh --seed
```

**Dashboard kosong setelah login**
Pastikan sudah seed data:

```bash
php artisan db:seed
```

## 📞 Support

Untuk pertanyaan atau bantuan development, silakan buat issue di repository ini.

## 📄 License

Open source - bebas digunakan untuk keperluan pendidikan.

---

**Dibuat dengan ❤️ menggunakan Laravel & Filament**
