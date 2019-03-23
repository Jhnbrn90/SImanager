<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SolventTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_solvent_has_a_trivial_name()
    {
        $solvent = factory('App\Solvent')->create(['trivial_name' => 'DCM']);
        $this->assertEquals('DCM', $solvent->trivial_name);
    }
}
