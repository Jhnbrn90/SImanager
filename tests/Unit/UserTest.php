<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_has_many_reactions()
    {
        $user = factory('App\User')->create();
        $reaction = factory('App\Reaction')->create(['user_id' => $user->id]);

        $this->assertTrue($user->reactions->contains($reaction));
    }

    /** @test **/
    public function a_user_has_a_preferred_prefix()
    {
        $user = factory('App\User')->create(['prefix' => 'JBN']);

        $this->assertEquals('JBN', $user->prefix);
    }
}
