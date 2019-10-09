<?php

namespace Tests\Feature;

use App\Compound;
use App\Project;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_default_project_is_created_for_newly_registered_users()
    {
        $user = factory(User::class)->create();

        $this->assertCount(1, $user->fresh()->projects);

        $project = $user->fresh()->projects->first();

        $this->assertEquals('Default project', $project->name);
    }

    /** @test **/
    public function a_project_can_get_all_of_its_compounds()
    {
        // Given we have a project
        $project = factory(Project::class)->create(['name' => 'Fake Project']);

        // Given we have some compounds
        $compounds = factory(Compound::class, 2)->create(['project_id' => $project]);
        $compoundFromDifferentProject = factory(Compound::class)->create();

        $this->assertTrue($project->compounds->contains($compounds[0]));
        $this->assertTrue($project->compounds->contains($compounds[1]));

        $this->assertFalse($project->compounds->contains($compoundFromDifferentProject));
    }
}
