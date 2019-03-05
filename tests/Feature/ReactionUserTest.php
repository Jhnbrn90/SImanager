<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReactionUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_generate_a_label_for_a_new_reaction()
    {
        $user = factory('App\User')->create(['prefix' => 'JBN']);
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

        $this->assertEquals('JBN_2', $user->newReactionLabel);
    }
}
