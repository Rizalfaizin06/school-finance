# Toggle Payment Mode - Individual vs Bulk

## Overview

Sistem pembayaran SPP sekarang memiliki 2 mode:

1. **Individual (Per Siswa)** - Pembayaran per siswa, bayar per bulan seperti sistem lama
2. **Bulk (Massal)** - Pembayaran massal, pilih banyak siswa dan bulan sekaligus

## Mode Pembayaran

### 1. Mode Individual (Per Siswa)

**Karakteristik:**

-   Fokus pada 1 siswa
-   Lihat status 12 bulan dalam 1 tabel
-   Bayar per bulan dengan klik tombol "Terima"
-   Mirip dengan sistem lama PembayaranSPP.php

**Flow:**

1. Pilih "Mode Pembayaran": **Individual (Per Siswa)**
2. Pilih "Jenis Pemasukan": SPP
3. Pilih "Tarif SPP": Contoh "SPP 2024/2025 - Rp 200.000"
4. Pilih "Akun Kas": Kas Tunai
5. Pilih "Metode Pembayaran": Cash/Transfer/QRIS
6. **Pilih Siswa**: Cari dan pilih 1 siswa
7. **Lihat Info Siswa**: NIS, Nama, Kelas, Sudah Bayar, Belum Bayar
8. **Tabel 12 Bulan**: Setiap baris = 1 bulan
    - Kolom: No, Bulan, Status, Tanggal Bayar, Nominal, No. Struk, Aksi
    - Bulan yang sudah bayar: badge hijau "✓ Lunas", tidak ada tombol
    - Bulan belum bayar: badge merah "✗ Belum Bayar", tombol "Terima (Rp 200.000)"
9. **Klik tombol "Terima"** untuk bayar bulan tertentu
10. Sistem create 1 Payment record untuk bulan tersebut

**Keunggulan:**

-   Cocok untuk orang tua yang datang bayar langsung
-   Detail per bulan terlihat jelas
-   History pembayaran tampil di tabel
-   Fokus pada 1 siswa, tidak bingung

### 2. Mode Bulk (Massal)

**Karakteristik:**

-   Bisa pilih banyak siswa sekaligus
-   Bisa pilih banyak bulan sekaligus
-   Filter berdasarkan kelas
-   Smart: hanya tampilkan siswa yang punya tagihan
-   Efisien untuk bayar nunggakan atau bayar di muka

**Flow:**

1. Pilih "Mode Pembayaran": **Bulk (Massal)**
2. Pilih "Jenis Pemasukan": SPP
3. Pilih "Tarif SPP": Contoh "SPP 2024/2025 - Rp 200.000"
4. Pilih "Akun Kas": Kas Tunai
5. Pilih "Metode Pembayaran": Cash/Transfer/QRIS
6. **Pilih Bulan**: Centang bulan-bulan yang akan dibayar (misal: Juli-Sept = 3 bulan)
7. **Filter Kelas**: Pilih kelas (misal: X IPA 1)
8. **Tabel Smart Students**:
    - Hanya tampilkan siswa yang punya bulan belum bayar
    - Kolom: Checkbox, No, NIS, Nama, "Bulan Belum Bayar" (badge), Total Tagihan
    - Contoh: Siswa A badge "2 bulan", total Rp 400.000 (karena dari 3 bulan yang dipilih, dia sudah bayar 1)
9. **Centang siswa** yang mau dibayar (bisa pilih banyak)
10. **Ringkasan**: Total siswa belum lunas, total dipilih, total pembayaran
11. **Klik "Bayar Massal"**
12. Sistem create Payment records untuk setiap siswa × bulan yang belum bayar

**Keunggulan:**

-   Efisien untuk pembayaran kolektif (misal: iuran kelas)
-   Hemat waktu: 20 siswa × 3 bulan = 1 klik (bukan 60 klik)
-   Auto-calculate tagihan berdasarkan bulan yang belum bayar
-   Cocok untuk nunggakan atau bayar di muka multiple bulan

## Kapan Pakai Mode Apa?

### Gunakan Mode Individual Jika:

-   Orang tua datang langsung untuk bayar anaknya
-   Ingin lihat detail history pembayaran per bulan
-   Bayar 1-2 bulan saja
-   Ingin fokus pada 1 siswa
-   Mirip kasir retail (1 customer at a time)

### Gunakan Mode Bulk Jika:

-   Bayar iuran kelas secara kolektif
-   Banyak siswa nunggak ingin dibayar sekaligus
-   Orang tua bayar beberapa bulan sekaligus (misal 6 bulan di muka)
-   Ingin efisiensi waktu (ratusan transaksi dalam sekali klik)
-   Mirip kasir grosir (batch processing)

## Perbandingan Visual

### Individual Mode UI:

```
┌─────────────────────────────────────────────────┐
│ Mode Pembayaran: [Individual (Per Siswa) ▼]     │
│ Jenis Pemasukan: [SPP ▼]                        │
│ Tarif SPP: [SPP 2024/2025 - Rp 200.000 ▼]       │
│ Akun Kas: [Kas Tunai ▼]                          │
│ Metode: [Cash ▼]                                 │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Pembayaran Individual                            │
│ Pilih Siswa: [1001 - Ahmad ▼]                    │
│                                                  │
│ NIS: 1001  Nama: Ahmad  Kelas: X IPA 1          │
│ Tarif: Rp 200.000  Sudah: 8/12  Belum: 4 bulan   │
└─────────────────────────────────────────────────┘

┌────┬────────────┬────────┬──────────┬────────────┬──────────┬────────────┐
│ No │ Bulan      │ Status │ Tgl Bayar│ Nominal    │ No Struk │ Aksi       │
├────┼────────────┼────────┼──────────┼────────────┼──────────┼────────────┤
│ 1  │ Juli 2024  │ ✓ Lunas│ 05/07/24 │ Rp 200.000 │ PMK-001  │ -          │
│ 2  │ Agus 2024  │ ✓ Lunas│ 06/08/24 │ Rp 200.000 │ PMK-025  │ -          │
│ 3  │ Sept 2024  │ ✗ Belum│ -        │ -          │ -        │ [Terima]   │
│ 4  │ Okt 2024   │ ✗ Belum│ -        │ -          │ -        │ [Terima]   │
...
```

### Bulk Mode UI:

```
┌─────────────────────────────────────────────────┐
│ Mode Pembayaran: [Bulk (Massal) ▼]              │
│ Jenis Pemasukan: [SPP ▼]                        │
│ Tarif SPP: [SPP 2024/2025 - Rp 200.000 ▼]       │
│ Akun Kas: [Kas Tunai ▼]                          │
│ Metode: [Cash ▼]                                 │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Pilih Bulan SPP                                  │
│ [✓] Juli 2024    [✓] Agustus 2024  [✓] Sept 2024│
│ [ ] Oktober 2024 [ ] November 2024 [ ] Des 2024  │
│ Bulan dipilih: 3 bulan | Total/siswa: Rp 600.000│
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Pilih Siswa                                      │
│ Filter Kelas: [X IPA 1 ▼]                        │
└─────────────────────────────────────────────────┘

┌────┬────┬──────┬────────┬──────────────────┬──────────────┐
│ [✓]│ No │ NIS  │ Nama   │ Bulan Belum Bayar│ Total Tagihan│
├────┼────┼──────┼────────┼──────────────────┼──────────────┤
│ [✓]│ 1  │ 1001 │ Ahmad  │ [2 bulan]        │ Rp 400.000   │
│ [✓]│ 2  │ 1002 │ Budi   │ [3 bulan]        │ Rp 600.000   │
│ [ ]│ 3  │ 1003 │ Citra  │ [1 bulan]        │ Rp 200.000   │
...

Ringkasan:
Total Siswa Belum Lunas: 15
Total Siswa Dipilih: 2
Total Pembayaran: Rp 1.000.000

[Bayar Massal]
```

## Technical Implementation

### Property Additions

```php
public $paymentMode = 'bulk'; // 'individual' or 'bulk'
public $individualStudentId = null;
public $individualMonthsData = [];
```

### New Methods (Individual Mode)

1. `resetForm()` - Reset form saat ganti mode
2. `loadIndividualMonths()` - Load 12 bulan untuk siswa yang dipilih
3. `generate12MonthsWithPaymentStatus()` - Generate 12 bulan dengan status bayar/belum
4. `getIndividualStudentInfoHtml()` - Tampilkan info siswa (NIS, nama, kelas, sudah/belum bayar)
5. `getIndividualMonthsTableHtml()` - Tampilkan tabel 12 bulan dengan tombol Terima
6. `payIndividualMonth($month, $year)` - Proses pembayaran 1 bulan untuk 1 siswa

### Visibility Logic

-   Mode toggle visible: `$this->isSppType()` (hanya untuk SPP)
-   Bulk sections visible: `$this->paymentMode === 'bulk'`
-   Individual section visible: `$this->paymentMode === 'individual'`

### Payment Processing

**Individual Mode:**

-   1 siswa × 1 bulan = 1 Payment record
-   Click button per month
-   Reload data after each payment

**Bulk Mode:**

-   Multiple students × multiple unpaid months = many Payment records
-   One click to process all
-   Reset selection after payment

## Benefits

### Untuk Bendahara:

1. **Fleksibilitas**: Bisa pilih mode sesuai situasi
2. **Efisiensi**: Bulk untuk mass payment, individual untuk detail
3. **User-Friendly**: UI jelas untuk masing-masing mode
4. **No Confusion**: Toggle jelas, mode terpisah

### Untuk Sekolah:

1. **Adaptif**: Cocok untuk berbagai skenario pembayaran
2. **Akurat**: Individual mode mencegah error saat fokus 1 siswa
3. **Cepat**: Bulk mode hemat waktu untuk pembayaran kolektif
4. **Traceable**: Semua payment tercatat dengan detail

## Migration from Old System

Sistem lama (PembayaranSPP.php) sekarang digantikan oleh **Individual Mode**.

-   Same UI concept: pilih siswa → lihat tabel 12 bulan → bayar per bulan
-   Enhanced dengan info siswa yang lebih lengkap
-   Terintegrasi dengan sistem baru (FeeRate, multi-mode)

## Testing Scenarios

### Individual Mode:

1. Pilih individual mode → pilih siswa → bayar 1 bulan → verify Payment created
2. Siswa sudah bayar sebagian → table shows correct paid/unpaid status
3. Bayar bulan yang sudah dibayar → warning muncul

### Bulk Mode:

1. Pilih bulk mode → pilih 3 bulan → select 5 siswa → bulk pay → verify Payments created
2. Siswa mixed status → table only shows unpaid months in tagihan
3. All students paid → table shows "semua lunas"

### Toggle:

1. Switch dari individual ke bulk → form reset
2. Switch dari bulk ke individual → form reset
3. Toggle hanya muncul untuk SPP, tidak muncul untuk kegiatan/seragam
