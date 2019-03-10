<?php

namespace Tests\Unit;

use App\User;
use App\Bundle;
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

    /** @test **/
    public function a_project_can_make_its_path()
    {
        $project = factory(Project::class)->create();
        $this->assertEquals('/projects/' . $project->id, $project->path());
    }

    /** @test **/
    public function a_project_belongs_to_a_bundle()
    {
        $bundle = factory('App\Bundle')->create(['name' => 'Test bundle']);
        $project = factory('App\Project')->create(['bundle_id' => $bundle->id]);

        $this->assertEquals($bundle->id, $project->bundle_id);
        $this->assertEquals('Test bundle', $project->bundle->name);
    }

    /** @test **/
    public function a_project_can_move_itself_to_a_different_bundle()
    {
        $user = factory('App\User')->create();
        $firstBundle = BundleFactory::ownedBy($user)->withProjects(1)->create();
        $secondBundle = BundleFactory::ownedBy($user)->withProjects(2)->create();

        $this->assertCount(1, $firstBundle->projects);
        $this->assertCount(2, $secondBundle->projects);

        $movingProject = tap($firstBundle->projects[0])->moveTo($secondBundle);

        $this->assertCount(0, $firstBundle->fresh()->projects);
        $this->assertCount(3, $secondBundle->fresh()->projects);

        $this->assertTrue($secondBundle->fresh()->projects->contains($movingProject));
    }

    /** @test **/
    public function a_project_knows_if_it_has_projects_associated()
    {
        $project = ProjectFactory::withCompounds(2)->create();

        $this->assertFalse($project->isEmpty());
    }
}
