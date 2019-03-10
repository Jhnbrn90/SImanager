<?php

namespace Tests\Feature;

use App\Compound;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddCompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function unauthenticated_users_can_not_add_new_compounds()
    {
        $this->get('/compounds/new')
          ->assertRedirect('/login');

        $this->post('/compounds')
          ->assertRedirect('/login');
    }

    /** @test **/
    public function authenticated_users_can_add_new_compounds()
    {
      $user = create('App\User');
      $project = create('App\Project', ['user_id' => $user->id]);

      $compound = make('App\Compound', ['project_id' => $project->id])->toArray();
      $compound['project'] = $project->id;

      $this->actingAs($user)->post("/compounds", $compound);

      $this->assertDatabaseHas('compounds', ['label' => $compound['label']]);

      $compound = Compound::where('label', $compound['label'])->first();

      $this->get($compound->path())->assertStatus(200)->assertSee($compound->label);
    }
}
