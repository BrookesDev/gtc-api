<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMultipleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->string('sub_total')->nullable();
            $table->string('total_vat')->nullable();
            $table->string('total_discount')->nullable();
            $table->string('total_price')->nullable();
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('sub_total')->nullable();
            $table->string('total_vat')->nullable();
            $table->string('total_discount')->nullable();
            $table->string('total_price')->nullable();
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('sub_total')->nullable();
            $table->string('total_vat')->nullable();
            $table->string('total_discount')->nullable();
            $table->string('total_price')->nullable();
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('sub_total')->nullable();
            $table->string('total_vat')->nullable();
            $table->string('total_discount')->nullable();
            $table->string('total_price')->nullable();
        });
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('sub_total')->nullable();
            $table->string('total_vat')->nullable();
            $table->string('total_discount')->nullable();
            $table->string('total_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('multiple_tables', function (Blueprint $table) {
            //
        });
    }
}
