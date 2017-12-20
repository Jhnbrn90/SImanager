<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compounds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('mol_id');
            $table->string('label');
            $table->string('formula');
            $table->float('molweight');
            $table->float('exact_mass');
            $table->boolean('proton_nmr');
            $table->boolean('carbon_nmr');
            $table->string('retention');
            $table->string('melting_point');
            $table->string('infrared');
            $table->float('mass_measured');
            $table->string('mass_adduct');
            $table->string('alpha_sign');
            $table->unsignedInteger('alpha_value');
            $table->string('alpha_concentration');
            $table->string('alpha_solvent');
            $table->mediumText('notes');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compounds');
    }
}
