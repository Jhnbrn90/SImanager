<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSupervisorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_supervisor', function (Blueprint $table) {
            $table->primary(['student_id', 'supervisor_id']);
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('supervisor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_supervisor');
    }
}
