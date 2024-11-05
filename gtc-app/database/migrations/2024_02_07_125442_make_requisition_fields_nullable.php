<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeRequisitionFieldsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisition', function (Blueprint $table) {
            $table->string('approval_order')->nullable()->change();
            $table->string('approver_reminant')->nullable()->change();
            $table->string('approved_by')->nullable()->change();
            $table->string('approval_status')->nullable()->change();
            $table->date('approved_date')->nullable()->change();
            $table->string('approver_list')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
