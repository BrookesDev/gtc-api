<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->date('date_of_invoice')->nullable();
            $table->text('description');
            $table->decimal('amount',18,2);
            $table->decimal('amount_paid',18,2);
            $table->decimal('balance',18,2);
            $table->string('type');
            $table->string('uuid');
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
        Schema::dropIfExists('my_transactions');
    }
}
