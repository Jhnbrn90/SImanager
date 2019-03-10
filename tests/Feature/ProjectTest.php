<?php

namespace Tests\Feature;

use App\User;
use App\Project;
use App\Compound;
use App\Reaction;
use Tests\TestCase;
use Facades\Tests\Setup\ProjectFactory;
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
    public function an_authenticated_user_can_delete_his_own_project()
    {
        $user = create('App\User');
        $project = ProjectFactory::ownedBy($user)->create();

        $this->assertDatabaseHas('projects', $project->toArray());

        $this->actingAs($user)->delete($project->path());

        $this->assertDatabaseMissing('projects', $project->toArray());
    }

    /** @test **/
    public function a_project_can_not_be_deleted_if_its_not_empty()
    {
        $user = create('App\User');
        $project = ProjectFactory::ownedBy($user)->withCompounds(2)->create();

        $this->actingAs($user)->delete($project->path())->assertStatus(422);
        $this->assertDatabaseHas('projects', $project->toArray());
    }

    /** @test **/
    public function a_user_may_only_delete_his_own_project()
    {
        $john = create('App\User');
        $frank = create('App\User');
        $project = ProjectFactory::ownedBy($john)->create();

        $this->actingAs($frank)->delete($project->path())->assertStatus(403);
        $this->assertDatabaseHas('projects', $project->toArray());
    }
}
