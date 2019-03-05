<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompoundReactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compound_reaction', function (Blueprint $table) {
            $table->unsignedInteger('compound_id');
            $table->unsignedInteger('reaction_id');

            $table->string('type');

            $table->unique(['compound_id', 'reaction_id']);

            $table->foreign('compound_id')->references('id')->on('compounds')->onDelete('cascade');
            $table->foreign('reaction_id')->references('id')->on('reactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compound_reaction');
    }
}
