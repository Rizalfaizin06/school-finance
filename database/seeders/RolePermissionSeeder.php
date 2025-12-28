<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Dashboard
            'view_dashboard',

            // Master Data
            'view_students',
            'create_students',
            'edit_students',
            'delete_students',

            'view_classes',
            'create_classes',
            'edit_classes',
            'delete_classes',

            'view_academic_years',
            'create_academic_years',
            'edit_academic_years',
            'delete_academic_years',

            'view_fee_types',
            'create_fee_types',
            'edit_fee_types',
            'delete_fee_types',

            'view_expense_categories',
            'create_expense_categories',
            'edit_expense_categories',
            'delete_expense_categories',

            'view_accounts',
            'create_accounts',
            'edit_accounts',
            'delete_accounts',

            // Transactions
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
            'print_receipt',

            'view_expenses',
            'create_expenses',
            'edit_expenses',
            'delete_expenses',
            'approve_expenses',

            // Reports
            'view_reports',
            'export_reports',

            // Settings
            'view_school_profile',
            'edit_school_profile',

            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles

        // 1. Admin - Full access
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // 2. Bendahara - Manage transactions and reports
        $bendaharaRole = Role::firstOrCreate(['name' => 'bendahara']);
        $bendaharaRole->givePermissionTo([
            'view_dashboard',

            'view_students',
            'create_students',
            'edit_students',

            'view_classes',
            'view_academic_years',
            'view_fee_types',
            'view_expense_categories',
            'view_accounts',

            'view_payments',
            'create_payments',
            'edit_payments',
            'print_receipt',

            'view_expenses',
            'create_expenses',
            'edit_expenses',

            'view_reports',
            'export_reports',

            'view_school_profile',
        ]);

        // 3. Kepala Sekolah - View only (monitoring)
        $kepsekRole = Role::firstOrCreate(['name' => 'kepala_sekolah']);
        $kepsekRole->givePermissionTo([
            'view_dashboard',
            'view_students',
            'view_classes',
            'view_academic_years',
            'view_fee_types',
            'view_expense_categories',
            'view_accounts',
            'view_payments',
            'view_expenses',
            'view_reports',
            'export_reports',
            'view_school_profile',
            'approve_expenses',
        ]);

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin.edu@rizalscompanylab.my.id'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // Create bendahara user
        $bendahara = User::firstOrCreate(
            ['email' => 'bendahara.edu@rizalscompanylab.my.id'],
            [
                'name' => 'Bendahara Sekolah',
                'password' => Hash::make('password'),
            ]
        );
        $bendahara->assignRole('bendahara');

        // Create kepala sekolah user
        $kepsek = User::firstOrCreate(
            ['email' => 'kepsek.edu@rizalscompanylab.my.id'],
            [
                'name' => 'Kepala Sekolah',
                'password' => Hash::make('password'),
            ]
        );
        $kepsek->assignRole('kepala_sekolah');

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Default users:');
        $this->command->info('Admin: admin.edu@rizalscompanylab.my.id / password');
        $this->command->info('Bendahara: bendahara.edu@rizalscompanylab.my.id / password');
        $this->command->info('Kepala Sekolah: kepsek.edu@rizalscompanylab.my.id / password');
    }
}
