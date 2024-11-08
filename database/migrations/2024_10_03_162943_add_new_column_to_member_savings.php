<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToMemberSavings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('member_savings', function (Blueprint $table) {
            $table->string('is_bank')->default(0);
            $table->string('teller_no')->nullable();
            $table->string('cheque_no')->nullable();
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
