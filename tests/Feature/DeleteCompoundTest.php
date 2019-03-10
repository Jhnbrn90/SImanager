<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteCompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function authenticated_users_can_delete_compounds()
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
}
