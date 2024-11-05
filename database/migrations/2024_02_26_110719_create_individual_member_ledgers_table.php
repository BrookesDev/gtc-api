<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndividualMemberLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individual_member_ledgers', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('company_id');
            $table->integer('account_id');
            $table->decimal('amount');
            $table->string('type');
            $table->string('description');
            $table->date('transaction_date');
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
        Schema::dropIfExists('individual_member_ledgers');
    }
}
