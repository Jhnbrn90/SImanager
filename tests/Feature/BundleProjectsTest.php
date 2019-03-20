<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\BundleFactory;
use Illuminate\Foundation\Testing\WithFaker;
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

        $this->get($movingProject->path() . '/edit')
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
}
