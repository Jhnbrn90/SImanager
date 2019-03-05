<?php

namespace Tests\Feature;

use App\User;
use App\Project;
use App\Compound;
use App\Reaction;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $project = factory(Project::class)->create(['name' => "Fake Project"]);

        // Given we have some compounds
        $compounds = factory(Compound::class, 2)->create(['project_id' => $project]);
        $compoundFromDifferentProject = factory(Compound::class)->create();

        $this->assertTrue($project->compounds->contains($compounds[0]));
        $this->assertTrue($project->compounds->contains($compounds[1]));
        
        $this->assertFalse($project->compounds->contains($compoundFromDifferentProject));
    }

    /** @test **/
    public function a_project_can_get_all_of_its_reactions()
    {
        $project = factory(Project::class)->create(['name' => "Fake Project"]);
        $reactions = factory(Reaction::class, 2)->create(['project_id' => $project]);
        $reactionFromDifferentProject = factory(Compound::class)->create();

        $this->assertTrue($project->reactions->contains($reactions[0]));
        $this->assertTrue($project->reactions->contains($reactions[1]));

        $this->assertFalse($project->reactions->contains($reactionFromDifferentProject));
    }
}
