<?php

namespace Tests\Feature;

use App\Compound;
use Tests\TestCase;
use Facades\Tests\Setup\ReactionFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function an_authenticated_user_can_view_his_reactions()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->actingAs($user)->get('/reactions')->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_reactions()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->get('/reactions')->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_view_a_single_reaction()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->actingAs($user)->get('/reactions/' . $reaction->id)->assertStatus(200);
    }

    /** @test **/
    public function guests_can_not_view_a_single_reaction()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->get('/reactions/' . $reaction->id)->assertRedirect('login');
    }

    /** @test **/
    public function a_user_can_not_view_reactions_of_others()
    {
        $john = create('App\User');
        $frank = create('App\User');
        $reaction = ReactionFactory::ownedBy($john)->create();

        $this->actingAs($frank)->get('/reactions/' . $reaction->id)->assertStatus(403);
    }

    /** @test **/
    public function an_authenticated_user_can_add_a_new_reaction_to_a_project()
    {
        $this->signIn($user = create('App\User'));

        $project = $user->projects->first();

        $this->assertCount(0, $project->fresh()->reactions);

        $this->actingAs($user)
            ->get('/reactions/new/' . $project->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('reactions', ['project_id' => $project->id]);

        $this->assertCount(1, $project->fresh()->reactions);
    }

    /** @test **/
    public function a_user_can_only_add_new_reaction_to_a_project_he_owns()
    {
        $john = create('App\User');
        $frank = create('App\User');

        $project = $john->projects->first();

        $this->assertCount(0, $project->fresh()->reactions);

        $this->actingAs($frank)
            ->get('/reactions/new/' . $project->id)
            ->assertStatus(403);

        $this->assertDatabaseMissing('reactions', ['project_id' => $project->id]);

        $this->assertCount(0, $project->fresh()->reactions);
    }

    /** @test **/
    public function an_authenticated_user_can_add_a_product_to_a_reaction()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();
        $molfile = file_get_contents(base_path() . '/tests/stubs/structure.php');

        $this->assertCount(0, $reaction->fresh()->products);

        $this->actingAs($user)->patch($reaction->path(), [
            'type'          => 'product',
            'molfile'       => $molfile,
            'molweight'     => '58.08',
            'formula'       => 'C3H6O',
            'exact_mass'    => '58.0419',
        ]);

        $compound = Compound::where('molweight', '58.08')->where('formula', 'C3H6O');

        $this->assertEquals(1, $compound->count());
        $this->assertCount(1, $reaction->fresh()->products);
        $this->assertTrue($reaction->fresh()->products->contains($compound->first()));
    }

    /** @test **/
    public function guests_can_not_add_a_product_to_a_reaction()
    {
        $reaction = ReactionFactory::create();
        $molfile = file_get_contents(base_path() . '/tests/stubs/structure.php');

        $this->assertCount(0, $reaction->fresh()->products);

        $this->patch($reaction->path(), [])->assertRedirect('/login');

        $this->assertCount(0, $reaction->fresh()->products);
    }

    /** @test **/
    public function a_user_can_not_add_products_to_another_users_reaction()
    {
        $john = create('App\User');
        $frank = create('App\User');

        $reaction = ReactionFactory::ownedBy($john)->create();

        $molfile = file_get_contents(base_path() . '/tests/stubs/structure.php');

        $this->assertCount(0, $reaction->fresh()->products);

        $this->actingAs($frank)->patch($reaction->path(), [
            'type'          => 'product',
            'molfile'       => $molfile,
            'molweight'     => '58.08',
            'formula'       => 'C3H6O',
            'exact_mass'    => '58.0419',
        ])->assertStatus(403);

        $compound = Compound::where('molweight', '58.08')->where('formula', 'C3H6O');

        $this->assertEquals(0, $compound->count());
        $this->assertCount(0, $reaction->fresh()->products);
        $this->assertFalse($reaction->fresh()->products->contains($compound->first()));
    }
}
