<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('item_id');
            $table->string('old_quantity');
            $table->string('new_quantity');
            $table->string('quantity');
            $table->string('stock_id');
            $table->decimal('amount',18,2);
            $table->string('created_by')->nullable();
            $table->string('company_id')->nullable();
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
        Schema::dropIfExists('stock_inventories');
    }
}
