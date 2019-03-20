<?php

namespace Tests\Unit;

use Tests\TestCase;
use Facades\Tests\Setup\BundleFactory;
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
        $bundle = create('App\Bundle', ['description' => 'Test bundle description']);

        $this->assertEquals('Test bundle description', $bundle->description);
    }

    /** @test **/
    public function a_bundle_belongs_to_a_user()
    {
        $user = create('App\User');
        $bundle = create('App\Bundle', ['user_id' => $user->id]);

        $this->assertEquals($user->id, $bundle->user_id);
        $this->assertEquals($user->fresh(), $bundle->user);
    }

    /** @test **/
    public function a_bundle_can_get_its_owner()
    {
        $user = create('App\User');
        $bundle = BundleFactory::ownedBy($user)->create();

        $this->assertTrue($user->is($bundle->owner));
    }

    /** @test **/
    public function a_bundle_can_fetch_its_projects()
    {
        $user = create('App\User');
        $bundle = create('App\Bundle', ['user_id' => $user->id]);
        $project = create('App\Project', ['bundle_id' => $bundle->id], 4);

        $this->assertCount(4, $bundle->projects);
    }
}
