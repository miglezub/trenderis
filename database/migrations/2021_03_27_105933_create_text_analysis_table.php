<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTextAnalysisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('text_analysis', function (Blueprint $table) {
            $table->id();
            $table->boolean('use_word2vec')->default(false);
            $table->boolean('use_idf')->default(false);
            $table->text('lemmatized_text');
            $table->text('results');
            $table->foreignId('text_id');
            $table->timestamps();

            $table->foreign('text_id')->references('id')->on('texts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('text_analysis');
    }
}
