<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGraphFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('graph_filters', function (Blueprint $table) {
            $table->id();
            $table->text('result');
            $table->date('date_from');
            $table->date('date_to');
            $table->foreignId('user_id');
            $table->foreignId('api_key_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('api_key_id')->references('id')->on('api_keys');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('texts', function (Blueprint $table) {
            $table->dropForeign(['api_key_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('graph_filters');
    }
}
