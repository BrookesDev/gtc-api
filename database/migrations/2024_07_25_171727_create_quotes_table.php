<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->string('document_number')->nullable();
            $table->string('reference')->nullable();
            $table->date('date')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('status')->nullable();
            $table->date('expiring_date')->nullable();
            $table->string('sales_rep')->nullable();
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
        Schema::dropIfExists('quotes');
    }
}
