<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeNullSomeFieldsInItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('description')->nullable()->change();
            // $table->integer('re_order_level')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->string('account_receivable')->nullable()->change();
            $table->string('category_id')->nullable()->change();
            $table->string('advance_payment_gl')->nullable()->change();
        });
        Schema::table('stock_inventories', function (Blueprint $table) {
            $table->decimal('amount',18,2)->nullable()->change();
            $table->string('quantity')->nullable()->change();
            $table->string('new_quantity')->nullable()->change();
            $table->string('old_quantity')->nullable()->change();
            $table->string('stock_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            //
        });
    }
}
