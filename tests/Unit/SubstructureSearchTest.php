<?php

namespace Tests\Unit;

use App\Structure;
use Tests\TestCase;
use App\Helpers\Facades\StructureFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubstructureSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_can_determine_candidates_for_cyclic_structures()
    {
        $structures = collect([
            'chlorobenzene' => base_path().'/tests/Molfiles/chlorobenzene.mol',
            'indole'        => base_path().'/tests/Molfiles/indole.mol',
            'cyclohexanone' => base_path().'/tests/Molfiles/cyclohexanone.mol',
        ])->map(function ($structure) {
            return StructureFactory::molfile(file_get_contents($structure))
                ->belongingTo('App\Chemical')
                ->create();
        });

        $molfile = file_get_contents(base_path().'/tests/Molfiles/benzene.mol');

        $candidates = Structure::chemicals()->candidates($molfile)->get();

        $this->assertCount(2, $candidates);

        $this->assertTrue($candidates->contains($structures['chlorobenzene']));
        $this->assertTrue($candidates->contains($structures['indole']));
        $this->assertFalse($candidates->contains($structures['cyclohexanone']));
    }

    /** @test **/
    public function it_can_determine_candidates_for_related_alcohols()
    {
        $structures = collect([
            'isopropanol'   => base_path().'/tests/Molfiles/2-propanol.mol',
            'propanol'    => base_path().'/tests/Molfiles/1-propanol.mol',
            'propanediol'   => base_path().'/tests/Molfiles/propanediol.mol',
        ])->map(function ($structure) {
            return StructureFactory::molfile(file_get_contents($structure))
                ->belongingTo('App\Chemical')
                ->create();
        });

        $molfile = file_get_contents(base_path().'/tests/Molfiles/1-propanol.mol');

        $candidates = Structure::chemicals()->candidates($molfile)->get();

        $this->assertCount(3, $candidates->fresh());

        $this->assertTrue($candidates->contains($structures['propanol']));
        $this->assertTrue($candidates->contains($structures['propanediol']));
        $this->assertTrue($candidates->contains($structures['isopropanol']));
    }

    /** @test **/
    public function it_can_filter_the_candidates_down_to_matches_based_on_2D_structure()
    {
        $structures = collect([
            'isopropanol'   => base_path().'/tests/Molfiles/2-propanol.mol',
            'propanol'    => base_path().'/tests/Molfiles/1-propanol.mol',
            'propanediol'   => base_path().'/tests/Molfiles/propanediol.mol',
        ])->map(function ($structure) {
            return StructureFactory::molfile(file_get_contents($structure))
                ->belongingTo('App\Chemical')
                ->create();
        });

        $molfile = file_get_contents(base_path().'/tests/Molfiles/1-propanol.mol');

        $matches = Structure::chemicals()->matches($molfile)->get();

        $this->assertEquals(2, $matches->count());

        $this->assertTrue($matches->contains($structures['propanol']));
        $this->assertTrue($matches->contains($structures['propanediol']));
        $this->assertFalse($matches->contains($structures['isopropanol']));
    }

    /** @test **/
    public function it_can_get_the_exact_match_for_a_given_substructure()
    {
        $structures = collect([
            'isopropanol'   => base_path().'/tests/Molfiles/2-propanol.mol',
            'propanol'    => base_path().'/tests/Molfiles/1-propanol.mol',
            'propanediol'   => base_path().'/tests/Molfiles/propanediol.mol',
        ])->map(function ($structure) {
            return StructureFactory::molfile(file_get_contents($structure))
                ->belongingTo('App\Chemical')
                ->create();
        });

        $molfile = file_get_contents(base_path().'/tests/Molfiles/1-propanol.mol');

        $matches = Structure::chemicals()->matches($molfile, $exact = true)->get();

        $this->assertEquals(1, $matches->count());

        $this->assertTrue($matches->contains($structures['propanol']));
        $this->assertFalse($matches->contains($structures['propanediol']));
        $this->assertFalse($matches->contains($structures['isopropanol']));
    }
}
