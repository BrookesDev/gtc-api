<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreColumnsToItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('image')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('sku')->nullable();
            $table->string('tax')->nullable();
            $table->string('discount_type')->nullable();
            $table->string('flat_discount')->nullable();
            $table->string('discount_percent')->nullable();
            $table->string('discount_price')->nullable();
            $table->integer('status')->default(1);

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
