<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToGeneralInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->decimal('supplied_amount', 18, 2)->nullable();
            $table->decimal('supplied_quantity', 18, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            //
        });
    }
}
