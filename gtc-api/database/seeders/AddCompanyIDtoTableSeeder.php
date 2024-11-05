<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddCompanyIDtoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //list of tables
        $tables = [
            // 'allowances',
            // 'allowance_amounts',
            // 'allowance_types',
            // 'banks',
            // 'beneficiaries',
            // 'deductions',
            // 'deduction_amounts',
            // 'deduction_types',
            // 'departments',
            // 'grades',
            // 'monthly_payrolls',
            // 'salary_structures',
            // 'staff_allowances',
            // 'staff_deductions',
            // 'steps',
            // 'taxes',
            // 'tax_deductions',
            // 'veriments',
            'levels'

        ];

        foreach ($tables as $table) {
            $sql = "ALTER TABLE $table ADD COLUMN company_id INT AFTER id";
            DB::statement($sql);
        }
    }
}
