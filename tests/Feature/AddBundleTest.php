<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddBundleTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function an_authenticated_user_can_make_a_new_bundle()
    {
        $user = factory('App\User')->create();

        $this->actingAs($user)->post('/bundles', ['name' => 'Custom Bundle']);

        $this->assertDatabaseHas('bundles', ['name' => 'Custom Bundle']);
    }

    /** @test **/
    public function guests_can_not_make_new_bundles()
    {
        $this->post('/bundles', ['name' => 'Custom Bundle'])->assertRedirect('/login');

        $this->assertDatabaseMissing('bundles', ['name' => 'Custom Bundle']);
    }

    /** @test **/
    public function a_new_bundle_is_associated_with_the_authenticated_user()
    {
        $user = factory('App\User')->create();

        $this->actingAs($user)->post('/bundles', ['name' => 'Custom Bundle']);

        $bundle = \App\Bundle::where('name', 'Custom Bundle')->first();

        $this->assertEquals($user->id, $bundle->user_id);
    }

    /** @test **/
    public function a_new_bundle_requires_a_name()
    {
        $user = factory('App\User')->create();

        $this->actingAs($user)
            ->post('/bundles', ['name' => ''])
            ->assertSessionHasErrors('name');
    }
}
