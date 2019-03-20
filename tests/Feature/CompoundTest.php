<?php

namespace Tests\Feature;

use App\Compound;
use Tests\TestCase;
use Facades\Tests\Setup\ProjectFactory;
use Facades\Tests\Setup\CompoundFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_view_his_compounds()
    {
        $this->signIn($user = create('App\User'));

        $compound = CompoundFactory::ownedBy($user)->create();

        $this->get('/compounds')
            ->assertStatus(200)
            ->assertSee($compound->label);
    }

    /** @test **/
    public function guests_can_not_view_compounds()
    {
        $this->get('/compounds')->assertRedirect('/login');
    }

    /** @test **/
    public function a_user_can_view_an_individual_compound()
    {
        $this->signIn($user = create('App\User'));
        $compound = CompoundFactory::ownedBy($user)->create();
        $this->get($compound->path())
            ->assertStatus(200)
            ->assertSee($compound->label)
            ->assertSee($compound->svgPath());
    }

    /** @test **/
    public function a_guest_can_not_view_an_individual_compound()
    {
        $compound = CompoundFactory::create();
        $this->get($compound->path())->assertRedirect('/login');
    }

    /** @test **/
    public function a_user_can_add_a_new_compound()
    {
        $this->signIn($user = create('App\User')); 
        $project = $user->projects->first();

        $this->get('/compounds/new')->assertStatus(200);

        $compound = factory('App\Compound')->raw(['project_id' => $project->id]);

        $this->post('/compounds', $compound);

        $this->assertDatabaseHas('compounds', $compound);
    }

    /** @test **/
    public function guests_can_not_add_new_compounds()
    {
        $this->get('/compounds/new')->assertRedirect('/login');

        $this->post('/compounds', [])->assertRedirect('/login');
    }

    /** @test **/
    public function a_user_can_only_add_compounds_to_his_own_projects()
    {
        $john = create('App\User');
        $frank = create('App\User');
        
        $project = $john->projects->first();
        $compound = factory('App\Compound')->raw(['project_id' => $project->id]);

        $this->actingAs($frank)->post('/compounds', $compound)->assertStatus(403);

        $this->assertDatabaseMissing('compounds', $compound);
        $this->assertCount(0, $project->compounds);
    }

    /** @test **/
    public function a_user_can_update_all_compound_properties()
    {
        $this->signIn($user = create('App\User'));

        $compound = CompoundFactory::ownedBy($user)->create();
        $newProject = ProjectFactory::ownedBy($user)->create();

        $this->get($compound->path() . '/edit')->assertStatus(200);


        $attributes = factory('App\Compound')->raw([
            'label'         => 'newLabel',
            'project_id'    =>  $newProject->id,
            'notes'         => 'Updated compound.',
        ]);

        $this->put($compound->path(), $attributes);
        $this->assertDatabaseHas('compounds', $attributes);
    }

    /** @test **/
    public function a_changed_molfile_updates_mass_and_formula_properties()
    {
        $this->signIn($user = create('App\User'));
        // strip last \n character from the file
        $molfile = substr(file_get_contents(base_path() . '/tests/stubs/structure.php'), 0, -1);

        $compound = CompoundFactory::ownedBy($user)->create();

        $attributes = [
            'label'         => 'newLabel',
            'notes'         => 'Updated compound.',
            'molfile'       => $molfile,
            'formula'       => 'C3H6O',
            'molweight'     => '200.1010',
            'exact_mass'    => '200.1011',
        ];

        $postAttributes = factory('App\Compound')->raw($attributes);

        // 'user_updated_molfile' is required for updating mass and formula 
        $postAttributes['user_updated_molfile'] = 'true';

        $this->put($compound->path(), $postAttributes)->assertRedirect($compound->path());

        $this->assertDatabaseHas('compounds', $attributes);

        $updatedCompound = Compound::where('label', 'newLabel')->where('notes', 'Updated compound.')->first();

        $this->assertEquals('C3H6O', $updatedCompound->formula);
        $this->assertEquals('200.1010', $updatedCompound->molweight);
        $this->assertEquals('200.1011', $updatedCompound->exact_mass);
    }

    /** @test **/
    public function an_unchanged_molfile_does_not_update_mass_and_formula_properties()
    {
        $this->signIn($user = create('App\User'));
        $compound = CompoundFactory::ownedBy($user)->create();

        $attributes = [
            'label'         => 'newLabel',
            'notes'         => 'Updated compound.',
            'formula'       => 'C3H6O',
            'molweight'     => '200.1010',
            'exact_mass'    => '200.1011',
        ];

        $postAttributes = factory('App\Compound')->raw($attributes);
        // 'user_updated_molfile' is required for updating mass and formula 
        $postAttributes['user_updated_molfile'] = 'false';

        $this->put($compound->path(), $postAttributes);

        $this->assertDatabaseMissing('compounds', $attributes);

        $updatedCompound = Compound::where('label', 'newLabel')->where('notes', 'Updated compound.')->first();

        $this->assertNotEquals('C3H6O', $updatedCompound->formula);
        $this->assertNotEquals('200.1010', $updatedCompound->molweight);
        $this->assertNotEquals('200.1011', $updatedCompound->exact_mass);
    }

    /** @test **/
    public function a_single_compound_property_can_be_updated()
    {
        $this->signIn($user = create('App\User'));
        $compound = CompoundFactory::ownedBy($user)->create();

        $this->patch($compound->path(), [
            'column'    => 'notes',
            'value'     => 'test note',
        ]);

        $this->patch($compound->path(), [
            'column'    => 'H_NMR_data',
            'value'     => '1H NMR Data: test',
        ]);

        $compound->refresh();

        $this->assertEquals('test note', $compound->notes);
        $this->assertEquals('1H NMR Data: test', $compound->H_NMR_data);
    }

    /** @test **/
    public function when_a_single_property_was_updated_it_returns_the_compound_json()
    {
        $this->signIn($user = create('App\User'));

        $compound = CompoundFactory::ownedBy($user)->create();
        
        $response = $this->json('PATCH', $compound->path(), [
            'column'    => 'notes',
            'value'     => 'test note',
        ]);

        $response->assertStatus(201);

        $compound = Compound::where('notes', 'test note')->first()->toArray();

        $this->assertEquals($compound, $response->json());
    }

    /** @test **/
    public function a_user_can_move_all_compounds_to_another_project()
    {
        $user = create('App\User');

        $defaultProject = $user->projects->first();

        $otherProject = ProjectFactory::ownedBy($user)->withCompounds(3)->create();

        $this->actingAs($user)->get("/project-compounds/{$otherProject->id}/edit")->assertStatus(200);

        $this->actingAs($user)->patch("/project-compounds/{$otherProject->id}", [
            'toProject' => $defaultProject->id,
        ]);

        $this->assertcount(3, $defaultProject->fresh()->compounds);
        $this->assertcount(0, $otherProject->fresh()->compounds);
    }

    /** @test **/
    public function a_user_can_not_move_projects_to_a_project_owned_by_another_user()
    {
        $john = factory('App\User')->create();
        $jane = factory('App\User')->create();

        $johnsProject = ProjectFactory::ownedBy($john)->withCompounds(3)->create();
        $janesProject = ProjectFactory::ownedBy($jane)->withCompounds(3)->create();

        $this->actingAs($john)
            ->patch("/project-compounds/{$johnsProject->id}", ['toProject' => $janesProject->id,])
            ->assertStatus(403);

        $this->assertCount(3, $janesProject->fresh()->compounds);
        $this->assertCount(3, $johnsProject->fresh()->compounds);
    }

    /** @test **/
    public function a_user_can_import_a_compound()
    {
        $user = create('App\User');

        $project = ProjectFactory::ownedBy($user)->withCompounds(1)->create();

        $this->actingAs($user)->get('/compounds/import')->assertStatus(200);

        $this->assertCount(1, Compound::all());

        $importedExperiment = 'RF = 0.50 (EtOAc/cHex = 3:7). 1H NMR (600 MHz, CDCl3) δ 7.20 (d, J = 8.6 Hz, 2H), 6.91 – 6.82 (m, 2H), 5.33 – 5.29 (m, 1H), 5.21 – 5.06 (m, 2H), 4.36 (dd, J = 75.4, 14.3 Hz, 2H), 3.80 (s, 3H), 3.20 (dd, J = 101.3, 10.9 Hz, 2H), 2.80 (d, J = 7.8 Hz, 1H), 2.53 (s, 3H), 1.66 – 1.47 (m, 2H), 0.85 (t, J = 7.4 Hz, 3H). 13C NMR (151 MHz, CDCl3) δ 201.61, 169.78, 159.42, 130.12, 129.69, 129.24, 128.09, 120.76, 114.22, 55.42, 47.71, 46.00, 44.46, 44.02, 39.07, 30.69, 23.15, 11.30. IR (neat): νmax (cm-1): 2987, 1679, 1512, 2358, 1215, 1174, 746. HRMS (ESI): m/z calculated for C19H24NO3 [M+H]+ = 314.1751, found = 314.1760. [α]20D = + 63.49 (c = 1.26, CHCl3).';

        $this->actingAs($user)->post('/compounds/import', [
            'experimental'  => $importedExperiment,
            'project'       => $project->id,
        ]);

        $this->assertCount(2, Compound::all());
        $this->assertCount(2, $project->fresh()->compounds);
    }

    /** @test **/
    public function a_user_can_delete_a_compound_he_owns()
    {
        $this->signIn($user = create('App\User'));

        $compound = CompoundFactory::ownedBy($user)->create();

        $this->assertDatabaseHas('compounds', $compound->toArray());

        $this->get($compound->path() . '/delete')->assertStatus(200);

        $this->delete($compound->path())->assertRedirect('/compounds');

        $this->assertDatabaseMissing('compounds', $compound->toArray());
    }

    /** @test **/
    public function a_user_can_not_delete_a_compound_he_does_not_own()
    {
        $frank = create('App\User');
        $john = create('App\User');

        $compound = CompoundFactory::ownedBy($john)->create();

        $this->actingAs($frank)->get($compound->path() . '/delete')->assertStatus(403);

        $this->actingAs($frank)->delete($compound->path())->assertStatus(403);

        $this->assertDatabaseHas('compounds', $compound->toArray());
    }

    /** @test **/
    public function guests_can_not_delete_compounds()
    {
        $compound = CompoundFactory::create();
        
        $this->get($compound->path() . '/delete')->assertRedirect('/login');

        $this->delete($compound->path())->assertRedirect('/login');
    }
}
