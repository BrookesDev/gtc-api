<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeAndOthersToGeneralInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->string('uuid')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('discount')->nullable();
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
