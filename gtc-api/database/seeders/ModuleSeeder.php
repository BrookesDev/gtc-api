<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = [
            // 'point_of_sales',
            // 'receipt',
            // 'receivables',
            // 'expenses',
            // 'payables',
            // 'accounting',
            // 'cooperative_management_system',
            // 'event_centers_mgt_system',
            // 'inventory',
            // 'fixed_asset',
            'payroll',
            'reconciliation',











        ];
        foreach ($modules as $module) {
            Module::create(['name' => $module, 'status' => '1']);
        }

    }
}
