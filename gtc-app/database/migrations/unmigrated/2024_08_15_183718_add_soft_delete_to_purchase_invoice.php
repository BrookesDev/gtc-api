<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeleteToPurchaseInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(schema::hasColumn('purchase_invoices','deleted_at')){
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->softDeletes();
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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            //
        });
    }
}
