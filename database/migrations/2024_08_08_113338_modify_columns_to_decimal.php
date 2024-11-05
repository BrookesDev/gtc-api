<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyColumnsToDecimal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          // Modify columns in sale_invoices
          Schema::table('sale_invoices', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 2)->change();
            $table->decimal('total_vat', 18, 2)->change();
            $table->decimal('total_discount', 18, 2)->change();
            $table->decimal('total_price', 18, 2)->change();
        });

        // Modify columns in quotes
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 2)->change();
            $table->decimal('total_vat', 18, 2)->change();
            $table->decimal('total_discount', 18, 2)->change();
            $table->decimal('total_price', 18, 2)->change();
        });

        // Modify columns in sales_orders
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 2)->change();
            $table->decimal('total_vat', 18, 2)->change();
            $table->decimal('total_discount', 18, 2)->change();
            $table->decimal('total_price', 18, 2)->change();
        });

        // Modify columns in purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 2)->change();
            $table->decimal('total_vat', 18, 2)->change();
            $table->decimal('total_discount', 18, 2)->change();
            $table->decimal('total_price', 18, 2)->change();
        });

        // Modify columns in purchase_invoices
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('sub_total', 18, 2)->change();
            $table->decimal('total_vat', 18, 2)->change();
            $table->decimal('total_discount', 18, 2)->change();
            $table->decimal('total_price', 18, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('decimal', function (Blueprint $table) {
            //
        });
    }
}
