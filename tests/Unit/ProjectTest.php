<?php

namespace Tests\Unit;

use App\Project;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_project_has_a_name()
    {
        $project = factory(Project::class)->create(['name' => 'Fake project']);

        $this->assertEquals('Fake project', $project->name);
    }

    /** @test **/
    public function a_project_has_a_description()
    {
        $project = factory(Project::class)->create(['description' => 'Fake project description']);

        $this->assertEquals('Fake project description', $project->description);
    }

    /** @test **/
    public function a_project_belongs_to_a_user()
    {
        $user = factory(User::class)->create(['name' => 'Project tester']);

        $project = factory(Project::class)->create(['user_id' => $user]);

        $this->assertEquals('Project tester', $project->user->name);
    }
}
