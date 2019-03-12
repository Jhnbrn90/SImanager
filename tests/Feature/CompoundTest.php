<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function users_can_list_their_compounds()
    {
        $this->signIn($user = create('App\User'));
        $compound = create('App\Compound', ['user_id' => $user->id]);

        $this->get('/compounds')->assertSee($compound->label);
    }

    /** @test **/
    public function guests_can_not_view_compounds()
    {
        $this->get('/compounds')->assertRedirect('/login');
    }

    /** @test **/
    public function authenticated_users_can_view_a_single_compound()
    {
        $this->signIn($user = create('App\User'));
        $compound = create('App\Compound', ['user_id' => $user->id]);

        $this->get($compound->path())
          ->assertSee($compound->label);
    }

    /** @test **/
    public function authenticated_users_can_delete_a_compound_they_own()
    {
        $this->signIn($user = create('App\User'));
        $compound = create('App\Compound', ['user_id' => $user->id]);

        $this->assertDatabaseHas('compounds', ['id' => $compound->id]);

        $this->delete("/compounds/{$compound->id}");

        $this->assertDatabaseMissing('compounds', ['id' => $compound->id]);
    }

    /** @test **/
    public function unauthenticated_users_can_not_delete_compounds()
    {
        $compound = create('App\Compound');

        $this->delete("/compounds/{$compound->id}")
          ->assertRedirect('/login');
    }

    /** @test **/
    public function an_authenticated_user_can_view_a_single_compound()
    {
        $this->signIn($user = create('App\User'));

        $compound = create('App\Compound', ['user_id' => $user->id]);

        $this->get($compound->path())->assertStatus(200)->assertSee($compound->label);
    }

    /** @test **/
    public function guests_can_not_view_other_users_compounds()
    {
        $compound = create('App\Compound');
        $this->get($compound->path())->assertRedirect('/login');
    }

    /** @test **/
    public function a_user_can_only_view_his_own_compounds()
    {
        $john = create('App\User');
        $bart = create('App\User');
        
        $compound = create('App\Compound', ['user_id' => $john]);
        
        $this->signIn($bart);

        $this->get($compound->path())->assertStatus(403);
    }
}
