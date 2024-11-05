<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalModule;
class ApprovalModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'Purchase Order',
            'Requisition'
        ];

        foreach ($permissions as $permission) {
            ApprovalModule::create(['name' => $permission]);
        }
    }
}
