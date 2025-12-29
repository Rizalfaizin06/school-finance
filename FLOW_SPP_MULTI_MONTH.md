# Flow Multi-Month SPP Payment System

## Overview

Sistem pembayaran SPP yang dapat menangani berbagai skenario real:

-   Siswa bayar tepat waktu per bulan
-   Siswa nunggak beberapa bulan, bayar sekaligus
-   Orang tua bayar beberapa bulan di muka
-   Siswa bayar cicilan (mis: nunggak 3 bulan, bayar 2 bulan dulu)

## Flow untuk Bendahara

### A. Pembayaran SPP (Multi-Bulan)

1. **Pilih Jenis Pemasukan**: SPP
2. **Pilih Tarif**: Contoh "SPP 2024/2025 - Rp 200.000"
    - Sistem auto-load tahun ajaran dari FeeRate
3. **Pilih Bulan**: Checkbox grid (Juli - Juni)
    - Bisa pilih 1 bulan (normal)
    - Bisa pilih multiple bulan (nunggak/bayar di muka)
    - Contoh: Centang Juli, Agustus, September untuk bayar 3 bulan
4. **Pilih Akun Kas**: Misal "Kas Tunai"

5. **Pilih Metode Pembayaran**: Cash/Transfer/QRIS

6. **Filter Kelas**: Pilih kelas untuk melihat siswa
    - Contoh: "X IPA 1"
7. **Tabel Siswa Cerdas**:

    - Sistem otomatis cek database Payment untuk setiap siswa
    - **Hanya tampilkan siswa yang punya bulan belum bayar**
    - Kolom yang ditampilkan:
        - Checkbox (untuk select)
        - No
        - NIS
        - Nama Siswa
        - **Bulan Belum Bayar** (badge kuning: "3 bulan")
        - **Total Tagihan** (teks merah: Rp 600.000)

    **Contoh Kasus**:

    - Bendahara centang Juli-Agustus-September
    - Siswa A: Sudah bayar Juli → badge "2 bulan", total Rp 400.000
    - Siswa B: Belum bayar semua → badge "3 bulan", total Rp 600.000
    - Siswa C: Sudah bayar semua → **TIDAK MUNCUL** di tabel

8. **Select & Bayar**:
    - Centang siswa yang mau dibayar
    - Ringkasan otomatis hitung:
        - Total Siswa Belum Lunas: 15
        - Total Siswa Dipilih: 5
        - **Total Pembayaran: Rp 2.500.000**
    - Klik "Bayar Massal"
9. **Proses Backend**:
    - Sistem loop setiap siswa yang dipilih
    - Untuk setiap siswa, query bulan yang belum dibayar
    - Create Payment record **per bulan yang belum bayar**
    - Contoh: Siswa A dipilih (nunggak 2 bulan) → create 2 Payment records:
        - Payment 1: Agustus 2024
        - Payment 2: September 2024
    - Receipt number unik per payment
    - Notes: "SPP August 2024", "SPP September 2024", dst

### B. Pembayaran Non-SPP (Kegiatan/Seragam)

1. **Pilih Jenis**: Kegiatan atau Seragam
2. **Pilih Item**: "Study Tour Bali - Rp 2.000.000"
3. **Pilih Akun & Metode**
4. **Filter Kelas**
5. **Tabel Siswa Sederhana**:
    - Semua siswa di kelas tampil
    - Kolom: Checkbox, No, NIS, Nama, Harga
6. **Select & Bayar**:
    - **1 siswa = 1 Payment record**
    - Tidak ada konsep bulan

## Keunggulan Sistem

### 1. Otomatis & Akurat

-   Tidak perlu cek manual siapa sudah bayar
-   Tabel otomatis filter siswa yang belum bayar
-   Hitung total otomatis berdasarkan bulan yang belum bayar

### 2. Flexible

-   Bisa bayar 1 bulan (normal)
-   Bisa bayar multiple bulan (nunggak/bayar di muka)
-   Bisa select multiple siswa sekaligus

### 3. Prevent Double Payment

-   Sistem cek database sebelum create payment
-   Jika sudah bayar, tidak akan ditampilkan atau sudah dipotong dari tagihan

### 4. Real-World Scenarios

**Scenario 1: Siswa Nunggak**

-   Bulan: Sekarang Desember
-   Pilih bulan: Juli-Desember (6 bulan)
-   Siswa A: Sudah bayar Juli-Agustus
-   Sistem tampilkan: "4 bulan belum bayar" (Sep-Des)
-   Total: Rp 800.000 (4 × Rp 200.000)

**Scenario 2: Bayar Di Muka**

-   Bulan: Sekarang Juli (awal semester)
-   Pilih bulan: Juli-Desember (6 bulan)
-   Siswa B: Belum bayar sama sekali
-   Orang tua mau bayar 6 bulan sekaligus
-   Sistem create 6 Payment records (Juli-Desember)

**Scenario 3: Cicilan Nunggakan**

-   Siswa C: Nunggak 5 bulan (Juli-November)
-   Bendahara pilih Juli-November
-   Sistem tampilkan: "5 bulan belum bayar", total Rp 1.000.000
-   Orang tua: "Bayar 2 bulan dulu"
-   Bendahara: pilih Juli-Agustus saja
-   Sistem create 2 Payment records
-   Sisa nunggakan: 3 bulan (Sep-Nov)

## Technical Implementation

### Database Schema

```sql
payments:
  - receipt_number (unique per payment)
  - student_id
  - fee_type_id
  - academic_year_id
  - month (nullable, for SPP only)
  - year (nullable, for SPP only)
  - amount
  - payment_method
  - notes
```

### Key Methods

1. `getSppStudentsTableHtml()`:

    - Loop students × selectedMonths
    - Query Payment table per student/month/year
    - Build unpaid_months array
    - Only show students with unpaid_count > 0
    - Calculate total_amount = unpaid_count × fee_rate_amount

2. `bulkPay()`:
    - If SPP:
        - Loop selectedStudents
        - For each student, query unpaid months
        - Create Payment per unpaid month
    - If non-SPP:
        - Create 1 Payment per selected student

### Front-end Components

1. Month Selector (checkbox grid):

```php
[✓] Juli 2024    [✓] Agustus 2024    [ ] September 2024
[✓] Oktober 2024 [ ] November 2024   [ ] Desember 2024
...
```

2. Smart Student Table:

```
| [✓] | No | NIS  | Nama        | Bulan Belum Bayar | Total Tagihan    |
|-----|-------|------|-------------|-------------------|------------------|
| [✓] | 1     | 1001 | Ahmad       | [3 bulan]         | Rp 600.000      |
| [✓] | 2     | 1002 | Budi        | [5 bulan]         | Rp 1.000.000    |
```

## Testing Checklist

### SPP Flow

-   [ ] Select SPP → pilih 1 bulan → siswa tampil benar
-   [ ] Select SPP → pilih multiple bulan → siswa tampil benar
-   [ ] Siswa sudah bayar semua → tidak muncul di tabel
-   [ ] Siswa bayar sebagian → badge dan total benar
-   [ ] Bulk pay → create Payment per unpaid month
-   [ ] Receipt number unik per payment
-   [ ] Notes menampilkan bulan yang benar

### Non-SPP Flow

-   [ ] Select Kegiatan → tabel sederhana tampil
-   [ ] Bulk pay → 1 Payment per siswa
-   [ ] Month dan year = null

### Edge Cases

-   [ ] Tidak pilih bulan SPP → muncul warning
-   [ ] Tidak pilih siswa → muncul warning
-   [ ] Semua siswa lunas → tampilkan pesan "Semua siswa sudah lunas"
-   [ ] Mixed payment states → handle correctly

## Benefits for Bendahara

1. **Hemat Waktu**:

    - Tidak perlu cek excel siapa sudah bayar
    - Tidak perlu hitung manual total tagihan
    - Bulk payment: 30 siswa × 3 bulan = 1 klik (instead of 90 klik)

2. **Akurat**:

    - Tidak ada double payment
    - Tidak ada lupa tagih bulan tertentu
    - Auto-calculate berdasarkan database real

3. **Transparent**:

    - Orang tua bisa lihat detail: "Sudah bayar bulan apa saja"
    - Bendahara bisa trace: Payment records jelas per bulan
    - Receipt per bulan untuk bukti

4. **Flexible**:
    - Support semua skenario (nunggak, bayar di muka, cicilan)
    - Tidak kaku "harus bayar bulan ini"
    - Sesuai kondisi real lapangan
