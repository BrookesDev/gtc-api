<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedbyToMemberloans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
            if (!Schema::hasColumn('member_loans', 'created_by')) {
                Schema::table('member_loans', function (Blueprint $table) {
                    $table->string('created_by')->nullable()->after('id');
                });
            }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member_loans', function (Blueprint $table) {
            //
        });
    }
}
