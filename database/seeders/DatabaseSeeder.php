<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        $this->call([
                // Step 1: Roles and Users
            RolePermissionSeeder::class,

                // Step 2: Master Data (Academic Year, Fee Types, Categories, Accounts)
            MasterDataSeeder::class,

                // Step 3: Classes (requires Academic Year)
            ClassRoomSeeder::class,

                // Step 4: Students (requires Classes)
            StudentSeeder::class,

                // Step 5: Payments (requires Students, Fee Types, Accounts)
            PaymentSeeder::class,

                // Step 6: Expenses (requires Categories, Accounts)
            ExpenseSeeder::class,

                // Step 7: Update Account Balances
            UpdateAccountBalanceSeeder::class,
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
    }
}
