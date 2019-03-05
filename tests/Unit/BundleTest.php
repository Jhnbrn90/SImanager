<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BundleTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_bundle_has_a_name()
    {
        $bundle = factory('App\Bundle')->create(['name' => 'Test bundle']);
        $this->assertEquals('Test bundle', $bundle->name);
    }

    /** @test **/
    public function a_bundle_has_a_description()
    {
        $bundle = factory('App\Bundle')->create(['description' => 'Test bundle description']);
        $this->assertEquals('Test bundle description', $bundle->description);
    }

    /** @test **/
    public function a_bundle_belongs_to_a_user()
    {
        $user = factory('App\User')->create();
        $bundle = factory('App\Bundle')->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $bundle->user_id);
        $this->assertEquals($user->fresh(), $bundle->user);
    }

    /** @test **/
    public function a_bundle_can_fetch_its_projects()
    {
        $user = factory('App\User')->create();
        $bundle = factory('App\Bundle')->create(['user_id' => $user->id]);
        $project = factory('App\Project', 4)->create(['bundle_id' => $bundle->id]);

        $this->assertCount(4, $bundle->projects);
    }
}
