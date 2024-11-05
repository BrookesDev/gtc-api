<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IndexAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all tables in the database
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            // Check if the table has a company_id column
            // if(!in_array($table,['all_transactions','customers'])){
                if (Schema::hasColumn($table, 'company_id')) {
                    // Get existing indexes on the table
                    $existingIndexes = DB::select(DB::raw("SHOW INDEX FROM `$table` WHERE Column_name = 'company_id'"));

                    // Add index to company_id column if it doesn't already exist
                    if (empty($existingIndexes)) {
                        DB::statement("ALTER TABLE `$table` ADD INDEX `{$table}_company_id_index` (`company_id`)");
                    }
                }
            // }
        }

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
