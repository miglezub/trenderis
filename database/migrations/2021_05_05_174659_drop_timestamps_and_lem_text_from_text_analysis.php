<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTimestampsAndLemTextFromTextAnalysis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('text_analysis', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropColumn('lemmatized_text');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('text_analysis', function (Blueprint $table) {
            $table->timestamps();
            $table->text('lemmatized_text');
        });
    }
}
