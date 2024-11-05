<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $permissions = [
            // 'manage-admin',
            // 'view-user',
            // 'create-user',
            // 'update-user',
            // 'delete-user',
            // 'view-role',
            // 'create-role',
            // 'update-role',
            // 'delete-role',
            // 'view-department',
            // 'create-department',
            // 'update-department',
            // 'delete-department',
            // 'view-session',
            // 'activate-session',
            // 'view-term',
            // 'activate-term',
            // 'view-approval-level',
            // 'create-approval-level',
            // 'update-approval-level',
            // 'delete-approval-level',
            // 'view-fee-setup',
            // 'create-fee-setup',
            // 'update-fee-setup',
            // 'delete-fee-setup',
            // 'manage-transaction',
            // 'view-beneficiary',
            // 'create-beneficiary',
            // 'update-beneficiary',
            // 'delete-beneficiary',
            // 'view-bank',
            // 'create-bank',
            // 'update-bank',
            // 'delete-bank',
            // 'create-payment-voucher',
            // 'approve-payment-voucher',
            // 'disapprove-payment-voucher',
            // 'delete-payment-voucher',
            // 'view-approved-paid-payment-voucher',
            // 'view-approved-pending-payment-voucher',
            // 'make-payment',
            // 'view-tax',
            // 'create-tax',
            // 'update-tax',
            // 'delete-tax',
            // 'view-tax-deduction',
            // 'view-school-income',
            // 'view-debtor-list',
            // 'manage-account',
            // 'view-category',
            // 'create-category',
            // 'edit-category',
            // 'delete-category',
            // 'view-account',
            // 'create-account',
            // 'update-account',
            // 'delete-account',
            // 'manage-report',
            // 'manage-general-ledger',
            // 'manage-cashbook',
            // 'manage-trial-balance',
            // 'manage-income-expenditure',
            // 'manage-balance-sheet',
            // 'view-budget',
            // 'upload-budget',
            // 'point-of-sales',
            'receipt',
            'receivables',
            'expenses',
            'payables',
            'accounting',
            'cooperative_management_system',
            'event_centers_mgt_system',
            'inventory',






        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum', 'status' => '1']);
        }

    }
}
