<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceUnitAccountToBookingExpenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_expenses', function (Blueprint $table) {
            $table->string('inventory_gl')->nullable();
            $table->string('cost_of_good_gl')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_labor_expenses', function (Blueprint $table) {
            //
        });
    }
}
