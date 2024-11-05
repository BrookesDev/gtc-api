<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSavingsType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('member_savings', function (Blueprint $table) {
            $table->string('savings_type')->nullable();
            $table->string('mode_of_savings')->nullable();
            $table->string('debit_account')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member_savings', function (Blueprint $table) {
            //
        });
    }
}
