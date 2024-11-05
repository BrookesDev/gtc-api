<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionDateToBookingLaborExpenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_labor_expenses', function (Blueprint $table) {
            $table->string('transaction_date')->nullable();
            $table->string('account_id')->nullable();
            $table->string('transaction_type')->nullable();
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
