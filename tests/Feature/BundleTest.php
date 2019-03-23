<?php

namespace Tests\Feature;

use App\Bundle;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BundleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        $this->user = create('App\User');
    }

    /** @test **/
    public function a_new_user_has_a_default_bundle()
    {
        $this->assertCount(1, $this->user->bundles);
        $this->assertEquals('Default bundle', $this->user->bundles->first()->name);
    }

    /** @test **/
    public function an_authenticated_user_can_add_a_new_bundle()
    {
        $this->actingAs($this->user)->get('/bundles/new')->assertStatus(200);

        $this->actingAs($this->user)->post('/bundles', ['name' => 'Custom Bundle']);

        $this->assertDatabaseHas('bundles', ['name' => 'Custom Bundle']);
    }

    /** @test **/
    public function guests_can_not_make_new_bundles()
    {
        $this->get('/bundles/new')->assertRedirect('/login');

        $this->post('/bundles', ['name' => 'Custom Bundle'])->assertRedirect('/login');

        $this->assertDatabaseMissing('bundles', ['name' => 'Custom Bundle']);
    }

    /** @test **/
    public function a_new_bundle_is_associated_with_the_authenticated_user()
    {
        $this->actingAs($this->user)->post('/bundles', ['name' => 'Custom Bundle']);

        $bundle = Bundle::where('name', 'Custom Bundle')->first();

        $this->assertEquals($this->user->id, $bundle->user_id);
    }

    /** @test **/
    public function a_new_bundle_requires_a_name()
    {
        $this->actingAs($this->user)
            ->post('/bundles', ['name' => ''])
            ->assertSessionHasErrors('name');
    }
}
