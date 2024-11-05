<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManyToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_transactions', function (Blueprint $table) {
            $table->string('currency')->nullable();
            $table->decimal('converted_amount', 18 ,2)->nullable();
            $table->decimal('currency_amount', 18 ,2)->nullable();
            $table->string('customer_id')->nullable();
            $table->string('supplier_id')->nullable();
            $table->string('pv_number')->nullable();
            $table->string('payment_bank')->nullable();
            $table->date('voucher_date')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('debit_gl_code')->nullable();
            $table->string('credit_gl_code')->nullable();
            $table->string('document')->nullable();
            $table->string('rate_id')->nullable();
            $table->string('payment_description')->nullable();
            $table->string('lodge_teller')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payable_type')->nullable();
            $table->string('receivable_type')->nullable();
            $table->string('loan_type')->nullable();
            $table->string('saving_type')->nullable();
            $table->string('bank_id')->nullable();
            $table->string('tie')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('my_transactions', function (Blueprint $table) {
            //
        });
    }
}
