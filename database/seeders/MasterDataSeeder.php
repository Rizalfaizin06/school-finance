<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\SchoolProfile;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Academic Year
        AcademicYear::create([
            'name' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
            'is_active' => true,
            'description' => 'Tahun Ajaran 2024/2025',
        ]);

        AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
            'description' => 'Tahun Ajaran 2025/2026',
        ]);

        // Fee Types
        FeeType::create([
            'name' => 'SPP',
            'category' => 'spp',
            'amount' => 200000,
            'frequency' => 'monthly',
            'description' => 'Sumbangan Pembinaan Pendidikan bulanan',
            'is_active' => true,
        ]);

        FeeType::create([
            'name' => 'Uang Kegiatan',
            'category' => 'non_spp',
            'amount' => 500000,
            'frequency' => 'yearly',
            'description' => 'Biaya kegiatan ekstrakurikuler dan pembelajaran',
            'is_active' => true,
        ]);

        FeeType::create([
            'name' => 'Seragam',
            'category' => 'non_spp',
            'amount' => 300000,
            'frequency' => 'once',
            'description' => 'Biaya pembelian seragam sekolah',
            'is_active' => true,
        ]);

        FeeType::create([
            'name' => 'Dana BOS',
            'category' => 'bos',
            'amount' => 0,
            'frequency' => 'monthly',
            'description' => 'Dana Bantuan Operasional Sekolah dari pemerintah',
            'is_active' => true,
        ]);

        // Expense Categories
        $expenseCategories = [
            ['name' => 'Gaji & Honorarium', 'code' => 'GAJI', 'description' => 'Gaji guru dan karyawan'],
            ['name' => 'ATK & Perlengkapan', 'code' => 'ATK', 'description' => 'Alat Tulis Kantor dan perlengkapan'],
            ['name' => 'Perawatan & Perbaikan', 'code' => 'PRWT', 'description' => 'Perawatan gedung dan fasilitas'],
            ['name' => 'Listrik & Air', 'code' => 'UTIL', 'description' => 'Biaya utilitas'],
            ['name' => 'Internet & Telepon', 'code' => 'KOMM', 'description' => 'Biaya komunikasi'],
            ['name' => 'Konsumsi', 'code' => 'KONS', 'description' => 'Biaya konsumsi kegiatan'],
            ['name' => 'Transport', 'code' => 'TRNS', 'description' => 'Biaya transportasi'],
            ['name' => 'Lain-lain', 'code' => 'LAIN', 'description' => 'Pengeluaran lainnya'],
        ];

        foreach ($expenseCategories as $category) {
            ExpenseCategory::create(array_merge($category, ['is_active' => true]));
        }

        // Accounts
        Account::create([
            'name' => 'Kas Tunai',
            'type' => 'cash',
            'opening_balance' => 10000000,
            'balance' => 10000000,
            'description' => 'Kas tunai sekolah',
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'Bank BRI',
            'type' => 'bank',
            'account_number' => '0123-4567-8901',
            'opening_balance' => 50000000,
            'balance' => 50000000,
            'description' => 'Rekening sekolah di Bank BRI',
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'account_number' => '1234-5678-9012',
            'opening_balance' => 30000000,
            'balance' => 30000000,
            'description' => 'Rekening sekolah di Bank Mandiri',
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'Bank BNI',
            'type' => 'bank',
            'account_number' => '9876-5432-1098',
            'opening_balance' => 20000000,
            'balance' => 20000000,
            'description' => 'Rekening sekolah di Bank BNI',
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'Dana BOS',
            'type' => 'bank',
            'account_number' => '5555-6666-7777',
            'opening_balance' => 100000000,
            'balance' => 100000000,
            'description' => 'Rekening khusus Dana BOS dari pemerintah',
            'is_active' => true,
        ]);

        // School Profile
        SchoolProfile::create([
            'name' => 'SD Negeri 1 Contoh',
            'npsn' => '12345678',
            'address' => 'Jl. Pendidikan No. 123, Jakarta',
            'phone' => '021-12345678',
            'email' => 'info@sdn1contoh.sch.id',
            'headmaster' => 'Drs. Ahmad Headmaster, M.Pd',
            'treasurer' => 'Siti Bendahara, S.Pd',
        ]);

        $this->command->info('Master data seeded successfully!');
    }
}
