# Database Seeder Documentation

## Overview

Database seeder untuk aplikasi School Finance Management telah dipisah per table untuk memudahkan development dan testing.

## Available Seeders

### 1. RolePermissionSeeder

**Fungsi**: Membuat roles, permissions, dan 3 default users
**Dependencies**: None
**Command**:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

**Data yang dibuat**:

-   3 Roles: admin, bendahara, kepala_sekolah
-   70+ Permissions
-   3 Default Users:
    -   admin.edu@rizalscompanylab.my.id / password
    -   bendahara.edu@rizalscompanylab.my.id / password
    -   kepsek.edu@rizalscompanylab.my.id / password

### 2. MasterDataSeeder

**Fungsi**: Membuat master data (Academic Year, Fee Types, Categories, Accounts)
**Dependencies**: None
**Command**:

```bash
php artisan db:seed --class=MasterDataSeeder
```

**Data yang dibuat**:

-   2 Academic Years (2024/2025 active, 2025/2026 inactive)
-   4 Fee Types (SPP, Uang Kegiatan, Seragam, Dana BOS)
-   8 Expense Categories
-   5 Accounts (Kas Tunai, Bank BRI, Bank Mandiri, Bank BNI, Dana BOS)
    -   Total opening balance: Rp 210.000.000
-   1 School Profile

### 3. ClassRoomSeeder

**Fungsi**: Membuat kelas 1A-6B
**Dependencies**: AcademicYear (dari MasterDataSeeder)
**Command**:

```bash
php artisan db:seed --class=ClassRoomSeeder
```

**Data yang dibuat**:

-   12 Classes (1A, 1B, 2A, 2B, 3A, 3B, 4A, 4B, 5A, 5B, 6A, 6B)

### 4. StudentSeeder

**Fungsi**: Membuat 96 siswa tersebar di 12 kelas
**Dependencies**: ClassRoom
**Command**:

```bash
php artisan db:seed --class=StudentSeeder
```

**Data yang dibuat**:

-   96 Students (8 siswa per kelas)

### 5. PaymentSeeder

**Fungsi**: Membuat transaksi pembayaran untuk 6 bulan terakhir
**Dependencies**: Student, FeeType, Account, AcademicYear
**Command**:

```bash
php artisan db:seed --class=PaymentSeeder
```

**Data yang dibuat**:

-   ~550-600 Payment transactions
-   Pembayaran SPP 6 bulan terakhir (70-90% siswa per bulan)
-   Pembayaran fee types lainnya
-   Data tersebar di semua accounts (Kas Tunai, Bank BRI, Bank Mandiri, Bank BNI, Dana BOS)

### 6. ExpenseSeeder

**Fungsi**: Membuat transaksi pengeluaran untuk 6 bulan terakhir
**Dependencies**: ExpenseCategory, Account, AcademicYear, User
**Command**:

```bash
php artisan db:seed --class=ExpenseSeeder
```

**Data yang dibuat**:

-   ~35-40 Expense transactions (5-8 per bulan)
-   Pengeluaran: Gaji, Listrik, Air, Internet, ATK, Kebersihan, dll
-   Data tersebar di semua accounts

### 7. UpdateAccountBalanceSeeder

**Fungsi**: Update saldo account berdasarkan transaksi
**Dependencies**: Account, Payment, Expense
**Command**:

```bash
php artisan db:seed --class=UpdateAccountBalanceSeeder
```

**Fungsi**:

-   Menghitung total income per account
-   Menghitung total expense per account
-   Update balance = opening_balance + income - expense

## Usage Examples

### Seed All (Fresh Install)

```bash
php artisan migrate:fresh --seed
```

**Urutan eksekusi**:

1. RolePermissionSeeder
2. MasterDataSeeder
3. ClassRoomSeeder
4. StudentSeeder
5. PaymentSeeder
6. ExpenseSeeder
7. UpdateAccountBalanceSeeder

### Seed Individual Table

**Re-generate students saja**:

```bash
php artisan db:seed --class=StudentSeeder
```

**Re-generate payments saja**:

```bash
php artisan db:seed --class=PaymentSeeder
# Jangan lupa update balance
php artisan db:seed --class=UpdateAccountBalanceSeeder
```

**Re-generate expenses saja**:

```bash
php artisan db:seed --class=ExpenseSeeder
# Jangan lupa update balance
php artisan db:seed --class=UpdateAccountBalanceSeeder
```

### Seed Partial (Hanya Master Data)

```bash
php artisan migrate:fresh
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=MasterDataSeeder
```

### Seed Partial (Tanpa Transaksi)

```bash
php artisan migrate:fresh
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=MasterDataSeeder
php artisan db:seed --class=ClassRoomSeeder
php artisan db:seed --class=StudentSeeder
```

## Important Notes

1. **Dependency Order**: Pastikan menjalankan seeder sesuai urutan dependency

    - ClassRoomSeeder membutuhkan AcademicYear
    - StudentSeeder membutuhkan ClassRoom
    - PaymentSeeder membutuhkan Student, FeeType, Account
    - ExpenseSeeder membutuhkan ExpenseCategory, Account, User

2. **Delete vs Truncate**: Semua seeder menggunakan `delete()` bukan `truncate()` karena foreign key constraint

3. **Account Distribution**: Payment dan Expense akan tersebar di semua 5 accounts secara random

4. **Balance Calculation**: Selalu jalankan `UpdateAccountBalanceSeeder` setelah seed Payment atau Expense

5. **Sample Data**: Data yang di-generate adalah dummy data untuk 6 bulan terakhir

## Expected Results

Setelah `migrate:fresh --seed`:

-   Users: 3 (admin, bendahara, kepsek)
-   Classes: 12
-   Students: 96
-   Academic Years: 2
-   Fee Types: 4
-   Expense Categories: 8
-   Accounts: 5
-   Payments: ~550-600 transaksi
-   Expenses: ~35-40 transaksi
-   Total Account Balance: ~Rp 297.523.338 (varies)

## Troubleshooting

### Error: Cannot truncate table

**Solution**: Sudah fixed menggunakan `delete()` instead of `truncate()`

### Error: Missing required data

**Solution**: Jalankan seeder dependency terlebih dahulu

### Balance tidak update

**Solution**: Jalankan `UpdateAccountBalanceSeeder` setelah seed Payment/Expense
