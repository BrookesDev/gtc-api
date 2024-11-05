<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeductionAmounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deduction_amounts', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('deduction_id');
            $table->integer('lower_level');
            $table->integer('upper_level');
            $table->integer('percentage');
            $table->integer('created_by');
            $table->decimal('fixed_amount',8,2);
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
        Schema::dropIfExists('deduction_amounts');
    }
}
