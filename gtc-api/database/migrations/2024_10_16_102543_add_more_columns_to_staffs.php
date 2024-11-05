<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreColumnsToStaffs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('staff_image')->nullable(); // Store path to image file
            $table->string('rsa_number')->nullable();
            // $table->string('qualification')->nullable();
            $table->string('state')->nullable();
            $table->string('lga')->nullable(); // Local Government Area
            // $table->string('step')->nullable();
            // $table->string('grade')->nullable();
            $table->string('level')->nullable();
            $table->string('dept_id')->nullable(); // Department ID (if foreign key)
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->date('employment_date')->nullable(); // Use date type for dates
            $table->string('account_number')->nullable();
            $table->string('account_bank')->nullable();
            $table->text('medical_condition')->nullable();
            $table->bigInteger('net_pay')->default(0);
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staffs', function (Blueprint $table) {
            //
        });
    }
}
