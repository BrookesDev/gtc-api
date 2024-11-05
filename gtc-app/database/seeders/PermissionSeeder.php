<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'view-loan-account',
            'create-loan-account',
            'update-loan-account',
            'delete-loan-account',
            'view-savings-account',
            'create-savings-account',
            'update-savings-account',
            'delete-savings-account',
            'manage-receivables',
            'view-customer',
            'create-customer',
            'update-customer',
            'delete-customer',
            'view-sales-invoice',
            'create-sales-invoice',
            'update-sales-invoice',
            'delete-sales-invoice',
            'view-advance-booking',
            'create-advance-booking',
            'update-advance-booking',
            'delete-advance-booking',
            'advance-booking-payments',
            'sales-invoice--payments',
            'view-loan-advances',
            'create-loan-advances',
            'manage-bulk-payment-excel',
            'manage-bulk-payment',
            'manage-savings-excel',
            'view-schedule-of-payment',
            'create-schedule-of-payment',
            'view-savings',
            'create-savings',
            'update-savings',
            'delete-savings',
            // 'view-department',
            // 'create-department',
            // 'update-department',
            // 'delete-department',
            'view-unit',
            'create-unit',
            'update-unit',
            'delete-unit',
            'view-items',
            'create-items',
            'update-items',
            'delete-items',
            'view-purchase-order',
            'create-purchase-order',
            'update-purchase-order',
            'delete-purchase-order',
            'view-requisition',
            'create-requisition',
            'update-requisition',
            'delete-requisition',
            'manage-purchase-delivery',
            'view-receipt',
            'create-receipt',
            'print-receipt',
            'view-expenses-account',
            'create-expenses-account',
            'print-expenses-account',
            'view-journal-entries',
            'create-journal-entries',
            'update-journal-entries',
            'delete-journal-entries',
            'manage-deposit-lodgment',
            'view-loan-repayment',
            'create-loan-repayment',
            'update-loan-repayment',
            'delete-loan-repayment',
            'view-loan-repayment-excel',
            'create-loan-repayment-excel',
            'update-loan-repayment-excel',
            'view-savings-withdrawal',
            'create-savings-withdrawal',
            'update-savings-withdrawal',
            'view-income-excel-upload',
            'create-income-excel-upload',
            'update-income-excel-upload',
            'view-payment-excel-upload',
            'create-payment-excel-upload',
            'update-payment-excel-upload',







        ];
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }
    }
}
