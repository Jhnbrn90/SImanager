<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_supervisor_can_impersonate_one_of_his_students()
    {
        $supervisor = create('App\User');
        $student = create('App\User');
        $student->addSupervisor($supervisor);

        $this->signIn($supervisor);

        $this->get('/compounds')->assertDontSee('Compounds of '.$student->name);

        $this->get('/users/'.$student->id.'/impersonate')
            ->assertRedirect('/')
            ->assertSessionHas('impersonate', $student->id)
            ->assertSessionHas('impersonator', $supervisor->id);

        $this->get('/compounds')->assertSee('Compounds of '.$student->name);
    }

    /** @test **/
    public function a_user_that_is_not_a_supervisor_can_not_impersonate_users()
    {
        $john = create('App\User');
        $frank = create('App\User');

        $this->actingAs($john)->get('/users/'.$frank->id.'/impersonate')->assertStatus(403);

        $this->actingAs($john)->get('/compounds')->assertDontSee('Compounds of '.$frank->name);
    }

    /** @test **/
    public function guests_can_not_impersonate_anyone()
    {
        $user = create('App\User');

        $this->get('/users/'.$user->id.'/impersonate')->assertRedirect('/login');
        $this->get('/compounds')->assertRedirect('/login');
    }
}
