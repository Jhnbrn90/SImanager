<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function an_authenticated_user_can_add_a_new_reaction()
    {
        $user = create('App\User');
        $project = create('App\Project', ['user_id' => $user->id]);

        $this->actingAs($user)->get('/reactions/new/' . $project->id)->assertStatus(200);

        $this->assertDatabaseHas('reactions', ['user_id' => $user->id]);

        $this->assertCount(1, $project->reactions);
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
