<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeleteToSomeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


     protected $tables = [
        'users',
        'account_statuses',
        'aging_buckets',
        'all_transactions',
        'approval_level',
        'approval_modules',
        'assign_company_users',
        'assign_modules',
        'beneficiaries',
        'beneficiary_accounts',
        'bookings',
        'booking_expenses',
        'booking_labor_expenses',
        'booking_payments',
        'budgets',
        'cashbooks',
        'categories',
        'category_accounts',
        'classes',
        'companies',
        'currencies',
        'customers',
        'customer_personal_ledgers',
        'departments',
        'exchange_rates',
        'general_invoices',
        'items',
        'journals',
        'member_loans',
        'member_savings',
        'model_has_permissions',
        'model_has_roles',
        'mode_of_savings',
        'modules',
        'my_transactions',
        'nominal_ledgers',
        'organisation_types',
        'payment_voucher_breakdowns',
        'payment_voucher_comments',
        'plans',
        'product_categories',
        'purchase_orders',
        'quotes',
        'receipts',
        'repayments',
        'requisition',
        'requisition_comments',
        'sales',
        'sales_orders',
        'sales_reps',
        'sale_invoices',
        'sale_transactions',
        'services',
        'stocks',
        'stock_inventories',
        'stock_received',
        'sub_sub_categories',
        'supplier_personal_ledgers',
        'supporting_documents',
        'taxes',
        'temp_journals',
        'transactions',
        'units',
        'roles',
        'permissions',
     
    ];
    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
}
