<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUnitNullableInItemsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('unit')->nullable()->change();
            $table->string('quantity')->nullable()->change();
            $table->string('category_id')->nullable()->change();
            $table->string('re_order_level')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items_tables', function (Blueprint $table) {
            //
        });
    }
}
