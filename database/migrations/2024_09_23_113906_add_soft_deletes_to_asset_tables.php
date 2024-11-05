<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToAssetTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $tables = [

        'asset_disposals',
        'fixed_asset_registers',
        'asset_transfers',
        'asset_categories',
        'asset_subcategories',
        'asset_register_documents',
        'disapproval_comments',
    ];
    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'deleted_at')) {
                    $table->softDeletes();
                }
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
        Schema::table('asset_tables', function (Blueprint $table) {
            //
        });
    }
}
