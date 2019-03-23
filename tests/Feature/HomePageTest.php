<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function when_a_user_visits_the_homepage_it_is_redirected_to_his_compound_overview()
    {
        $this->signIn();

        $this->get('/')->assertRedirect('/compounds');
    }

    /** @test **/
    public function when_guests_visits_the_homepage_they_are_redirected_to_the_login_page()
    {
        $this->get('/')->assertRedirect('/login');
    }
}
