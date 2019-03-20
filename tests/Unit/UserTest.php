<?php

namespace Tests\Unit;

use Tests\TestCase;
use Facades\Tests\Setup\ReactionFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_retrieve_its_reactions()
    {
        $user = factory('App\User')->create();
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->assertTrue($user->reactions()->contains($reaction->fresh()));
    }

    /** @test **/
    public function a_user_has_a_preferred_prefix()
    {
        $user = factory('App\User')->create(['prefix' => 'JBN']);

        $this->assertEquals('JBN', $user->prefix);
    }
}
