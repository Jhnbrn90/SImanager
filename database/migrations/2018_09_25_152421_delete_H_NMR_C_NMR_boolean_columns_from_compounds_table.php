<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteHNMRCNMRBooleanColumnsFromCompoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('proton_nmr', 'carbon_nmr')) {
            Schema::table('compounds', function (Blueprint $table) {
                $table->dropColumn('proton_nmr');
                $table->dropColumn('carbon_nmr');
            });
        }
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
