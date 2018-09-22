<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SharingDataTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_add_a_supervisor()
    {
        // given we have a supervisor and a student
        $supervisor = factory(User::class)->create(['name' => 'Supervisor']);
        $student = factory(User::class)->create(['name'  => 'Student']);

        // By default, the student doesn't have any supervisors
        $this->assertCount(0, $student->supervisors);

        // When this student adds his supervisor to be able to view the data 
        $student->addSupervisor($supervisor);

        // we expect that the associated supervisor can be retrieved 
        $this->assertCount(1, $student->fresh()->supervisors);
        $this->assertEquals('Supervisor', $student->fresh()->supervisors->first()->name);
    }

    /** @test **/
    public function a_supervisor_can_list_all_corresponding_students()
    {
        // given we have a supervisor and a student
        $supervisor = factory(User::class)->create(['name' => 'Supervisor']);
        $student = factory(User::class)->create(['name'  => 'Student']);

        // By default, the supervisor doesn't have any students
        $this->assertCount(0, $supervisor->fresh()->students);

        // When this student adds his supervisor to be able to view the data 
        $student->addSupervisor($supervisor);

        // we expect the following student to be retrieved
        $this->assertCount(1, $supervisor->fresh()->students);
        $this->assertEquals('Student', $supervisor->fresh()->students->first()->name);
    }
}
