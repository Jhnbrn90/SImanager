<?php

namespace Tests\Feature;

use App\Compound;
use Tests\TestCase;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

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

        $this->actingAs($john)->patch("/project-compounds/{$johnsProject->id}", [
            'toProject' => $janesProject->id,
        ])->assertStatus(403);

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
}
