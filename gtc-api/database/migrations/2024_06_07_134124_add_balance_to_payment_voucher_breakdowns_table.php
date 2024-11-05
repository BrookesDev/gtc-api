<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBalanceToPaymentVoucherBreakdownsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_voucher_breakdowns', function (Blueprint $table) {
            $table->decimal('balance',18 ,2)->nullable();
            $table->string('expense')->nullable()->change();
        });

        Schema::table('monthly_deductions', function (Blueprint $table) {
            $table->string('uuid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_voucher_breakdowns', function (Blueprint $table) {
            //
        });
    }
}
