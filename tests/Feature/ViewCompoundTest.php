<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewCompoundTest extends TestCase
{
    use RefreshDatabase;

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
