<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeManyFieldInPurchaseOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('document_number');
            $table->string('uuid');
            $table->string('status');
            $table->string('expiring_date');
            $table->string('reference');
            $table->string('approver_list')->nullable()->change();
            $table->string('created_by')->nullable()->change();
            $table->string('order_by')->nullable()->change();
            $table->string('item')->nullable()->change();
            $table->string('quantity')->nullable()->change();
            $table->string('order_id')->nullable()->change();
            $table->string('company_id')->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            //
        });
    }
}
