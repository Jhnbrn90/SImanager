<?php

namespace Tests\Feature;

use App\Project;
use Tests\TestCase;
use Facades\Tests\Setup\BundleFactory;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_default_project_is_created_for_newly_registered_users()
    {
        $user = create('App\User');

        $this->assertCount(1, $user->fresh()->projects);

        $project = $user->fresh()->projects->first();

        $this->assertEquals('Default project', $project->name);
    }

    /** @test **/
    public function a_user_can_list_all_his_projects()
    {
        $this->signIn($user = create('App\User'));

        $projectOne = ProjectFactory::withCompounds(1)->ownedBy($user)->create();
        $projectTwo = ProjectFactory::withCompounds(4)->ownedBy($user)->create();

        $this->get('/projects')->assertStatus(200)
            ->assertSee($projectOne->name)
            ->assertSee($projectTwo->name);
    }

    /** @test **/
    public function guests_can_not_list_projects()
    {
        $this->get('/projects')->assertRedirect('/login');
    }

    /** @test **/
    public function a_user_can_view_a_single_project()
    {
        $user = create('App\User');
        $project = ProjectFactory::withCompounds(1)->ownedBy($user)->create();

        $this->actingAs($user)->get($project->path())->assertStatus(200)->assertSee($project->name);
    }

    /** @test **/
    public function guests_can_not_view_a_single_project()
    {
        $project = ProjectFactory::create();

        $this->get($project->path())->assertRedirect('/login');
    }

    /** @test **/
    public function users_can_not_view_the_project_of_others()
    {
        $john = create('App\User');
        $frank = create('App\User');
        $project = ProjectFactory::withCompounds(1)->ownedBy($john)->create();

        $this->actingAs($frank)->get($project->path())->assertStatus(403);
    }

    /** @test **/
    public function a_user_can_add_a_new_project()
    {
        $this->signIn();

        $this->get('/projects/create')->assertStatus(200);

        $project = factory('App\Project')->raw();
        
        $this->post('/projects', $project)->assertRedirect('/projects');

        $this->assertDatabaseHas('projects', $project);
    }

    /** @test **/
    public function a_new_project_must_be_associated_with_an_existing_bundle()
    {
        $this->signIn();

        $this->post('/projects', $project = factory('App\Project')->raw(['bundle_id' => null]))
            ->assertSessionHasErrors('bundle_id');

        $this->assertDatabaseMissing('projects', $project);

        $this->post('/projects', $project = factory('App\Project')->raw(['bundle_id' => 2]))
            ->assertSessionHasErrors('bundle_id');

        $this->assertDatabaseMissing('projects', $project);
    }

    /** @test **/
    public function a_new_project_can_only_be_added_to_a_bundle_the_user_owns()
    {
        $notOwnedBundle = BundleFactory::create();

        $this->signIn($user = create('App\User'));
        
        $project = factory('App\Project')->raw(['bundle_id' => $notOwnedBundle->id]);
        
        $this->post('/projects', $project)->assertStatus(403);   
        $this->assertDatabaseMissing('projects', $project);
    }

    /** @test **/
    public function guests_can_not_add_projects()
    {
        $project = factory('App\Project')->raw();
        
        $this->post('/projects', $project)->assertRedirect('/login');

        $this->assertDatabaseMissing('projects', $project);
    }

    /** @test **/
    public function a_user_can_update_their_project()
    {
        $this->signIn($user = create('App\User'));

        $project = ProjectFactory::ownedBy($user)->create();

        $projectAttributes = [
            'name'          => 'Updated project',
            'description'   => 'This project was updated.',
            'bundle_id'     =>  $project->bundle->id,
        ];

        $this->get("{$project->path()}/edit")->assertStatus(200);

        $this->patch($project->path(), $projectAttributes);

        $this->assertDatabaseHas('projects', $projectAttributes);
    }

    /** @test **/
    public function guests_can_not_update_a_project()
    {
        $project = ProjectFactory::create();
        $projectAttributes = [
            'name'          => 'Updated project',
            'description'   => 'This project was updated.',
            'bundle_id'     =>  $project->bundle->id,
        ];

        $this->get("{$project->path()}/edit")->assertRedirect('/login');

        $this->patch($project->path(), $projectAttributes)->assertRedirect('/login');

        $this->assertDatabaseMissing('projects', $projectAttributes);
    }

    /** @test **/
    public function a_user_can_only_update_their_own_projects()
    {
        $john = create('App\User');
        $frank = create('App\User');
        $project = ProjectFactory::ownedBy($john)->create();
        $projectAttributes = [
            'name'          => 'Updated project',
            'description'   => 'This project was updated.',
            'bundle_id'     =>  $project->bundle->id,
        ];

        $this->actingAs($frank)->patch($project->path(), $projectAttributes)->assertStatus(403);

        $this->assertDatabaseMissing('projects', $projectAttributes);
    }

    /** @test **/
    public function a_user_can_export_all_compounds_belonging_to_a_project()
    {
        $this->signIn($user = create('App\User'));
        $project = ProjectFactory::ownedBy($user)->withCompounds(2)->create();
        $compoundOne = create('App\Compound', ['project_id' => $project->id]);
        $compoundTwo = create('App\Compound', ['project_id' => $project->id]);

        $this->get("{$project->path()}/export")
            ->assertStatus(200)
            ->assertSee($compoundOne->label)
            ->assertSee($compoundTwo->label);
    }

    /** @test **/
    public function users_can_delete_a_project_they_own()
    {
        $user = create('App\User');
        $project = ProjectFactory::ownedBy($user)->create();

        $this->assertDatabaseHas('projects', $project->toArray());

        $this->actingAs($user)->delete($project->path());

        $this->assertDatabaseMissing('projects', $project->toArray());
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

    /** @test **/
    public function a_project_can_only_be_deleted_if_it_is_empty()
    {
        $user = create('App\User');
        $project = ProjectFactory::ownedBy($user)->withCompounds(2)->create();

        $this->actingAs($user)->delete($project->path())->assertStatus(422);

        $this->assertDatabaseHas('projects', $project->toArray());
    }
}
