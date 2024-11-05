<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addcompanyid_to_all_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tables = [
            // 'accounts',
            'assets',
            'asset_disposals',
            'asset_suppliers',
            'beneficiaries',
            'bookings',
            'budgets',
            'cashbooks',
            'customers',
            'departments',
            'employees',
            'exchange_rates',
            'items',
            'journals',
            'loan_accounts',
            'payments',
            'payment_postings',
            'payment_vouchers',
            'purchase_ledgers',
            'purchase_orders',
            'receipts',
            'requisition',
            'sales',
            'staff',
            'stocks',
            'suppliers',
            'tax_deductions',
            'users',
            'veriments',




        ];

        foreach ($tables as $table) {
            $sql = "ALTER TABLE $table ADD COLUMN company_id INT NULL AFTER id";
            DB::statement($sql);
        }
    }
}
