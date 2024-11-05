<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('stocks');
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('stock_id')->nullable();
            $table->string('name');
            $table->string('description');
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->string('classification')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('unit_of_measurement');
            $table->float('quantity')->nullable();
            $table->integer('re_order_level')->nullable();
            $table->string('stock_uuid')->nullable();
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_tables');
    }
}
