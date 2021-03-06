<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->string('label');
            $table->string('formula')->nullable();
            $table->string('molweight')->nullable();
            $table->string('exact_mass')->nullable();
            $table->boolean('proton_nmr')->nullable();
            $table->boolean('carbon_nmr')->nullable();
            $table->string('retention')->nullable();
            $table->string('melting_point')->nullable();
            $table->string('infrared')->nullable();
            $table->string('mass_measured')->nullable();
            $table->string('mass_calculated')->nullable();
            $table->string('mass_adduct')->nullable();
            $table->string('alpha_sign')->nullable();
            $table->string('alpha_value')->nullable();
            $table->string('alpha_concentration')->nullable();
            $table->string('alpha_solvent')->nullable();
            $table->mediumText('notes')->nullable();
            $table->mediumText('molfile')->nullable();

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
