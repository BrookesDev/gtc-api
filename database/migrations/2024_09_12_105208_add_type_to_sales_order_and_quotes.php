<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToSalesOrderAndQuotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('quotes','type')){
            Schema::table('quotes', function (Blueprint $table) {
                $table->string('type')->nullable();
             });
        }
        if(!Schema::hasColumn('sales_orders','type')){
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->string('type')->nullable();
             });
        }
        if(!Schema::hasColumn('sale_invoices','type')){
            Schema::table('sale_invoices', function (Blueprint $table) {
                $table->string('type')->nullable();
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
        Schema::table('quotes', function (Blueprint $table) {
            //
        });
    }
}
