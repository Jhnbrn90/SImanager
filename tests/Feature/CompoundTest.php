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

        $this->signIn();
        $compound = create('App\Compound');

        $this->get('/')->assertSee($compound->label);
   }

   /** @test **/
   public function unauthenticated_users_are_can_not_list_compounds_but_are_redirected_to_auth_page()
   {
        $this->withExceptionHandling();

        $this->get('/')->assertRedirect('/login');
   }

   /** @test **/
   public function authenticated_users_can_view_a_single_compound()
   {
      $this->signIn();
      $compound = create('App\Compound');

      $this->get($compound->path())
        ->assertSee($compound->label);
   }

}
