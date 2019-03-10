<?php

namespace Tests\Feature;

use App\Compound;
use Tests\TestCase;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportingCompoundsTest extends TestCase
{
    use RefreshDatabase;

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
