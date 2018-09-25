<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteHNMRCNMRBooleanColumnsFromCompoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('compounds', function (Blueprint $table) {
            $table->dropColumn('proton_nmr');
            $table->dropColumn('carbon_nmr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('compounds', function (Blueprint $table) {
            $table->boolean('proton_nmr')->nullable();
            $table->boolean('carbon_nmr')->nullable();
        });
    }
}
