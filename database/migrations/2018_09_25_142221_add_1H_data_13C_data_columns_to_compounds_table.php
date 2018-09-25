<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Add1HData13CDataColumnsToCompoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('compounds', function (Blueprint $table) {
            $table->mediumText('H_NMR_data')->nullable();
            $table->mediumText('C_NMR_data')->nullable();
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
            $table->dropColumn('1H_NMR_data');
            $table->dropColumn('13C_NMR_data');
        });
    }
}
