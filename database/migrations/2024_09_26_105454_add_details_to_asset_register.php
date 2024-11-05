<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToAssetRegister extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fixed_asset_registers', function (Blueprint $table) {
            $table->string('asset_code')->nullable();
            $table->string('depreciation_rate')->nullable();
            $table->string('asset_purchased')->nullable();
            $table->string('depre_method')->nullable();
            $table->string('depre_cal_period')->nullable();
            $table->string('depre_expenses_account')->nullable();
            $table->string('accumulated_depreciation')->nullable();
            $table->string('asset_gl')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fixed_asset_registers', function (Blueprint $table) {
            //
        });
    }
}
