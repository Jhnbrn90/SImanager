<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
   public function authenticated_users_can_list_their_compounds()
   {
        $this->withoutExceptionHandling();
        // given we have an authenticated user
        $this->signIn();

        // given we have a compound which belongs to the user

        // and this user has a compound assigned to it
        $compound = create('App\Compound', ['user_id' => auth()->id()]);

        // if we visit the homepage
        $this->get('/')->assertSee($compound->label);

   }

   /** @test **/
   public function unauthenticated_users_are_redirected_to_auth_page()
   {
        $this->withExceptionHandling();
        $this->get('/')->assertRedirect('/login');
   }
}
