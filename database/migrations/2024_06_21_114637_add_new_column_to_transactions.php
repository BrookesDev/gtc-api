<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_transactions', function (Blueprint $table) {
            $table->string('bank_logded')->nullable();
            $table->date('date_logded')->nullable();
            $table->string('logded_by')->nullable();
        });
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->decimal('supplied_price',18,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('my_transactions', function (Blueprint $table) {
            //
        });
    }
}
