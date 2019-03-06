<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_project_must_belong_to_an_existing_bundle()
    {
        $this->signIn();

        $this->post('/projects', factory('App\Project')->raw([
            'bundle_id' => null
        ]))->assertSessionHasErrors('bundle_id');

        $this->post('/projects', factory('App\Project')->raw([
            'bundle_id' => 2
        ]))->assertSessionHasErrors('bundle_id');

        $project = factory('App\Project')->raw([
            'bundle_id' => create('App\Bundle')
        ]);
        
        $this->post('/projects', $project)->assertRedirect('/projects');

        $this->assertDatabaseHas('projects', $project);
    }

    /** @test **/
    public function guests_can_not_add_projects()
    {
        $project = factory('App\Project')->raw([
            'bundle_id' => create('App\Bundle')
        ]);
        
        $this->post('/projects', $project)->assertRedirect('/login');

        $this->assertDatabaseMissing('projects', $project);
    }
}
