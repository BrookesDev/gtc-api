<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecTypeToAllowanceAmountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('allowance_amounts', function (Blueprint $table) {
            $table->string('spec_type')->after('allowance_id'); // Position it after the allowance_id

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allowance_amounts', function (Blueprint $table) {
            //
        });
    }
}
