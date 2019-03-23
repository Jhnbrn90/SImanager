<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SharingDataTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_add_a_supervisor()
    {
        $supervisor = create('App\User', ['name' => 'Supervisor']);
        $student = create('App\User', ['name'  => 'Student']);

        $this->assertCount(0, $student->supervisors);

        $student->addSupervisor($supervisor);

        $this->assertCount(1, $student->fresh()->supervisors);

        $this->assertEquals('Supervisor', $student->fresh()->supervisors->first()->name);
    }

    /** @test **/
    public function a_supervisor_can_list_all_corresponding_students()
    {
        $supervisor = create('App\User', ['name' => 'Supervisor']);
        $student = create('App\User', ['name'  => 'Student']);

        $student->addSupervisor($supervisor);

        $this->assertCount(1, $supervisor->fresh()->students);

        $this->assertEquals('Student', $supervisor->fresh()->students->first()->name);
    }
}
