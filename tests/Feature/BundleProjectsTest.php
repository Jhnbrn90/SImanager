<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\BundleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BundleProjectsTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_move_a_project_to_a_different_bundle()
    {
        $this->signIn($user = create('App\User'));
        $bundleOne = BundleFactory::ownedBy($user)->withProjects(1)->create();
        $bundleTwo = BundleFactory::ownedBy($user)->withProjects(1)->create();

        $movingProject = $bundleOne->projects->first();

        $this->get($movingProject->path().'/edit')
            ->assertStatus(200)
            ->assertSee($bundleTwo->name);

        $this->patch($movingProject->path(), [
            'name'      => 'Updated name',
            'bundle_id' => $bundleTwo->id,
        ]);

        $this->assertEquals($bundleTwo->fresh(), $movingProject->fresh()->bundle);
    }

    /** @test **/
    public function a_user_can_not_move_a_project_to_a_bundle_they_do_not_own()
    {
        $john = create('App\User');
        $frank = create('App\User');

        $johnsBundle = BundleFactory::ownedBy($john)->withProjects(1)->create();
        $franksBundle = BundleFactory::ownedBy($frank)->withProjects(1)->create();

        $movingProject = $johnsBundle->projects->first();

        $this->actingAs($john)->patch($movingProject->path(), [
            'name'      => 'Updated.',
            'bundle_id' => $franksBundle->id,
        ])->assertStatus(403);

        $this->assertEquals($johnsBundle->fresh(), $movingProject->fresh()->bundle);
        $this->assertCount(1, $franksBundle->projects);
    }

    /** @test **/
    public function a_user_can_move_all_projects_to_another_bundle()
    {
        $user = create('App\User');
        $firstBundle = BundleFactory::ownedBy($user)->withProjects(2)->create();
        $secondBundle = BundleFactory::ownedBy($user)->withProjects(1)->create();
        $this->actingAs($user)->get("/bundle-projects/{$firstBundle->id}/edit")->assertStatus(200);

        $this->actingAs($user)->patch("/bundle-projects/{$firstBundle->id}", [
            'toBundle' => $secondBundle->id,
        ]);
        $this->assertcount(0, $firstBundle->fresh()->projects);
        $this->assertcount(3, $secondBundle->fresh()->projects);
    }

    /** @test **/
    public function a_user_can_not_move_projects_to_a_bundle_he_does_not_own()
    {
        $john = create('App\User');
        $frank = create('App\User');

        $johnsBundle = BundleFactory::ownedBy($john)->withProjects(1)->create();
        $franksBundle = BundleFactory::ownedBy($frank)->withProjects(1)->create();

        $this->actingAs($frank)->patch("/bundle-projects/{$franksBundle->id}", [
            'toBundle'  => $johnsBundle->id,
        ])->assertStatus(403);

        $this->assertcount(1, $johnsBundle->fresh()->projects);
        $this->assertcount(1, $franksBundle->fresh()->projects);
    }

    /** @test **/
    public function a_user_can_not_move_projects_from_a_bundle_they_do_not_own()
    {
        $john = create('App\User');
        $frank = create('App\User');

        $johnsBundle = BundleFactory::ownedBy($john)->withProjects(1)->create();
        $franksBundle = BundleFactory::ownedBy($frank)->withProjects(1)->create();

        $this->actingAs($frank)->get("/bundle-projects/{$johnsBundle->id}/edit")->assertStatus(403);

        $this->actingAs($frank)->patch("/bundle-projects/{$johnsBundle->id}", [
            'toBundle'  => $franksBundle->id,
        ])->assertStatus(403);

        $this->assertcount(1, $johnsBundle->fresh()->projects);
        $this->assertcount(1, $franksBundle->fresh()->projects);
    }
}
