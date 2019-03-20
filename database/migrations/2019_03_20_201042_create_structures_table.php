<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chemical_id')->nullable();
            $table->unsignedTinyInteger('n_atoms');
            $table->unsignedTinyInteger('n_bonds');
            $table->unsignedTinyInteger('n_rings')->default(0);
            $table->unsignedTinyInteger('n_C')->default(0);
            $table->unsignedTinyInteger('n_C1')->default(0);
            $table->unsignedTinyInteger('n_C2')->default(0);
            $table->unsignedTinyInteger('n_CHB1p')->default(0);
            $table->unsignedTinyInteger('n_CHB2p')->default(0);
            $table->unsignedTinyInteger('n_CHB3p')->default(0);
            $table->unsignedTinyInteger('n_CHB4')->default(0);
            $table->unsignedTinyInteger('n_O2')->default(0);
            $table->unsignedTinyInteger('n_O3')->default(0);
            $table->unsignedTinyInteger('n_N1')->default(0);
            $table->unsignedTinyInteger('n_N2')->default(0);
            $table->unsignedTinyInteger('n_N3')->default(0);
            $table->unsignedTinyInteger('n_S')->default(0);
            $table->unsignedTinyInteger('n_SeTe')->default(0);
            $table->unsignedTinyInteger('n_F')->default(0);
            $table->unsignedTinyInteger('n_Cl')->default(0);
            $table->unsignedTinyInteger('n_Br')->default(0);
            $table->unsignedTinyInteger('n_I')->default(0);
            $table->unsignedTinyInteger('n_P')->default(0);
            $table->unsignedTinyInteger('n_B')->default(0);
            $table->unsignedTinyInteger('n_Met')->default(0);
            $table->unsignedTinyInteger('n_X')->default(0);
            $table->unsignedTinyInteger('n_b1')->default(0);
            $table->unsignedTinyInteger('n_b2')->default(0);
            $table->unsignedTinyInteger('n_b3')->default(0);
            $table->unsignedTinyInteger('n_bar')->default(0);
            $table->unsignedTinyInteger('n_C1O')->default(0);
            $table->unsignedTinyInteger('n_C2O')->default(0);
            $table->unsignedTinyInteger('n_CN')->default(0);
            $table->unsignedTinyInteger('n_XY')->default(0);
            $table->unsignedTinyInteger('n_r3')->default(0);
            $table->unsignedTinyInteger('n_r4')->default(0);
            $table->unsignedTinyInteger('n_r5')->default(0);
            $table->unsignedTinyInteger('n_r6')->default(0);
            $table->unsignedTinyInteger('n_r7')->default(0);
            $table->unsignedTinyInteger('n_r8')->default(0);
            $table->unsignedTinyInteger('n_r9')->default(0);
            $table->unsignedTinyInteger('n_r10')->default(0);
            $table->unsignedTinyInteger('n_r11')->default(0);
            $table->unsignedTinyInteger('n_r12')->default(0);
            $table->unsignedTinyInteger('n_r13p')->default(0);
            $table->unsignedTinyInteger('n_rN')->default(0);
            $table->unsignedTinyInteger('n_rN1')->default(0);
            $table->unsignedTinyInteger('n_rN2')->default(0);
            $table->unsignedTinyInteger('n_rN3p')->default(0);
            $table->unsignedTinyInteger('n_rO')->default(0);
            $table->unsignedTinyInteger('n_rO1')->default(0);
            $table->unsignedTinyInteger('n_rO2p')->default(0);
            $table->unsignedTinyInteger('n_rS')->default(0);
            $table->unsignedTinyInteger('n_rX')->default(0);
            $table->unsignedTinyInteger('n_rar')->default(0);
            $table->longText('molfile')->nullable();
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
        Schema::dropIfExists('structures');
    }
}
