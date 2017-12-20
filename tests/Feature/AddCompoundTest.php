<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddCompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function unauthenticated_users_can_not_add_new_compounds()
    {
        $this->withExceptionHandling();

        $this->get('/compounds/new')
          ->assertRedirect('/login');

        $this->post('/compounds')
          ->assertRedirect('/login');
    }

    /** @test **/
    public function authenticated_users_can_add_new_compounds()
    {
      $this->withoutExceptionHandling();

      $this->signIn();

      $compound = make('App\Compound');

      $this->post("/compounds", $compound->toArray());

      $this->assertDatabaseHas('compounds', ['label' => $compound->label]);

      $compound = \App\Compound::where('label', $compound->label)->first();

      $this->get($compound->path())
        ->assertSee($compound->label);
    }

    /** @test **/
    public function a_molfile_is_created_when_a_new_compound_is_added_to_the_database()
    {
        $compound = create('App\Compound');

        $compound->toMolfile('Here goes the SD file contents');

        $this->assertFileExists(storage_path() . "/app/molfiles/{$compound->id}.mol");
    }

    /** @test **/
    public function an_svg_image_is_prepared_when_a_new_compound_is_added_to_the_database()
    {
        $compound = create('App\Compound');

        $compound->toSVG();

        $this->assertFileExists(storage_path() . "/app/svg/{$compound->id}.svg");
    }

}
