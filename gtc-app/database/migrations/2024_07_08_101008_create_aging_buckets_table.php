<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgingBucketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aging_buckets', function (Blueprint $table) {
            $table->id();
            $table->integer('min_days')->unsigned();
            $table->integer('max_days')->unsigned();
            $table->string('description', 255);
            $table->timestamps();
        });

        DB::table('aging_buckets')->insert([
            ['min_days' => 0, 'max_days' => 30, 'description' => '0-30 days'],
            ['min_days' => 31, 'max_days' => 60, 'description' => '31-60 days'],
            ['min_days' => 61, 'max_days' => 90, 'description' => '61-90 days'],
            ['min_days' => 91, 'max_days' => 120, 'description' => '91-120 days'],
            ['min_days' => 121, 'max_days' => 9999, 'description' => 'above 120 days'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aging_buckets');
    }
}
