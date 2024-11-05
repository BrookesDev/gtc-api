<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewPermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $level = [
            ['name' => 'Manage Product', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Add Products', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Product List', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Receivables', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Customer', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Quote', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Sales Invoice', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Sales Order', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Manage Customers' Receipt", 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Schedule of Receivable', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Receivable Aged Analysis', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Payables', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Suppliers', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Purchase Order', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Purchases Invoice', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => "Manage Suppliers' Payment", 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Schedule Payables', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Payables Aged Analysis', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Banking', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Expenses', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Payment Voucher', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Bank Transactions', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Bulk Expenses Postings', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage General Ledger', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Charts of Account Creation', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Journal Entries', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Reports', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Account Activity Report', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Cashbook', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Income Statement', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manage Statement of Financial Position', 'guard_name' => 'sanctum', 'created_at' => now(), 'updated_at' => now()],



        ];

        // Insert the data into the levels table
        DB::table('permissions')->insert($level);
    }
}
