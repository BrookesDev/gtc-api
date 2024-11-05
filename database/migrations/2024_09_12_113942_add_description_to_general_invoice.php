<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToGeneralInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_invoices', function (Blueprint $table) {
            $table->string('service_description')->nullable();
            $table->integer('account_id')->nullable();
            $table->decimal('total_service_amount',18,2)->nullable();
            $table->decimal('service_amount',18,2)->nullable();
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
