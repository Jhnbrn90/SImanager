<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\ReactionFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function an_authenticated_user_can_view_his_reactions()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->actingAs($user)->get('/reactions')->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_reactions()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->get('/reactions')->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_view_a_single_reaction()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->actingAs($user)->get('/reactions/' . $reaction->id)->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_a_single_reaction()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->get('/reactions/' . $reaction->id)->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_not_view_reactions_of_others()
    {
        $john = create('App\User');
        $frank = create('App\User');
        $reaction = ReactionFactory::ownedBy($john)->create();

        $this->actingAs($frank)->get('/reactions/' . $reaction->id)->assertStatus(403);
    }

    /** @test **/
    public function an_authenticated_user_can_add_a_new_reaction()
    {
        $this->withoutExceptionHandling();

        $this->signIn($user = create('App\User'));

        $project = $user->projects->first();

        $this->actingAs($user)
            ->get('/reactions/new/' . $project->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('reactions', ['project_id' => $project->id]);

        $this->assertCount(1, $project->fresh()->reactions);
    }

    // /** @test **/
    // public function an_authenticated_user_can_add_components_to_a_reaction()
    // {
    //     $user = factory('App\User')->create();
    //     $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

    //     $this->actingAs($user)->patch($reaction->path(), [
    //         'type'  => 'product',
    //     ]);
    // }
}
