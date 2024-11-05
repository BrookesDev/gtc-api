<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingLaborExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_labor_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('booking_id');
            $table->string('uuid');
            $table->string('action_by');
            $table->text('description');
            $table->decimal('amount',18,2);
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
        Schema::dropIfExists('booking_labor_expenses');
    }
}
