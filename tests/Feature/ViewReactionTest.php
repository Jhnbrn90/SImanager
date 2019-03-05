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
        $user = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

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
        $user = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/reactions/' . $reaction->id)->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_a_single_reaction()
    {
        $user = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

        $this->get('/reactions/' . $reaction->id)->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_not_view_reactions_of_others()
    {
        $this->withoutExceptionHandling();

        $user = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);
        $user2 = factory('App\User')->create();

        $this->actingAs($user2)->get('/reactions/' . $reaction->id)->assertRedirect('/reactions');
    }

    /** @test **/
    public function a_supervisor_can_its_students_reactions()
    {
        $student = factory('App\User')->create();
        $supervisor = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $student->id]);

        $this->actingAs($student)->get('/reactions/' . $reaction->id)->assertStatus(200);
        $this->actingAs($supervisor)->get('/reactions/' . $reaction->id)->assertRedirect('/reactions');

        $student->addSupervisor($supervisor);

        $this->actingAs($supervisor)->get('/reactions/' . $reaction->id)->assertStatus(200);
    }
}
