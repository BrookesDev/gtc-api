<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowanceAmountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('allowance_amounts', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('allowance_id');
            $table->integer('lower_level');
            $table->integer('upper_level');
            $table->integer('percentage');
            $table->decimal('fixed_amount',20,2)->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('allowance_amounts');
    }
}
