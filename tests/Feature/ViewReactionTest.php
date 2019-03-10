<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function an_authenticated_user_can_view_his_reactions()
    {
        $user = create('App\User');
        $reaction = create('App\Reaction', ['user_id' => $user->id]);

        $this->actingAs($user)->get('/reactions')->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_reactions()
    {
        $user = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

        $this->get('/reactions')->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_view_a_single_reaction()
    {
        $user = create('App\User');
        $reaction = create('App\Reaction', ['user_id' => $user->id]);

        $this->actingAs($user)->get('/reactions/' . $reaction->id)->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_a_single_reaction()
    {
        $user = create('App\User');
        $reaction = create('App\Reaction', ['user_id' => $user->id]);

        $this->get('/reactions/' . $reaction->id)->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_not_view_reactions_of_others()
    {
        $john = create('App\User');
        $reaction = create('App\Reaction', ['user_id' => $john->id]);
        $frank = create('App\User');

        $this->actingAs($frank)->get('/reactions/' . $reaction->id)->assertStatus(403);
    }

    /** @test **/
    public function a_supervisor_can_view_its_students_reactions()
    {
        $student = create('App\User');
        $supervisor = create('App\User');
        $reaction = create('App\Reaction', ['user_id' => $student->id]);

        $this->actingAs($student)->get($reaction->path())->assertStatus(200);
        $this->actingAs($supervisor)->get($reaction->path())->assertStatus(403);

        $student->addSupervisor($supervisor);

        $this->actingAs($supervisor)->get($reaction->path())->assertStatus(200);
    }
}
