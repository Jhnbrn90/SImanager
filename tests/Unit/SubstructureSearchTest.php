<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\Facades\StructureFactory;
use App\Helpers\Facades\SubstructureSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubstructureSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_can_filter_down_to_matches_based_on_properties()
    {
        $structures = collect([
            'chlorobenzene' => base_path().'/tests/Molfiles/chlorobenzene.mol',
            'indole'        => base_path().'/tests/Molfiles/indole.mol',
            'cyclohexanone' => base_path().'/tests/Molfiles/cyclohexanone.mol',
        ])->map(function ($structure) {
            return StructureFactory::molfile(file_get_contents($structure))->create();
        });

        $query = file_get_contents(base_path().'/tests/Molfiles/benzene.mol');

        $candidates = SubstructureSearch::molfile($query)->candidates();

        $this->assertCount(2, $candidates);

        $this->assertTrue($candidates->contains($structures['chlorobenzene']));
        $this->assertTrue($candidates->contains($structures['indole']));
        $this->assertFalse($candidates->contains($structures['cyclohexanone']));

        $structures = collect([
            'isopropanol'   => base_path().'/tests/Molfiles/2-propanol.mol',
            'propanol'    => base_path().'/tests/Molfiles/1-propanol.mol',
            'propanediol'   => base_path().'/tests/Molfiles/propanediol.mol',
        ])->map(function ($structure) {
            return StructureFactory::molfile(file_get_contents($structure))->create();
        });

        $query = file_get_contents(base_path().'/tests/Molfiles/1-propanol.mol');
        $candidates = SubstructureSearch::molfile($query)->candidates();

        $this->assertEquals(3, $candidates->count());

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
            return StructureFactory::molfile(file_get_contents($structure))->create();
        });

        $query = file_get_contents(base_path().'/tests/Molfiles/1-propanol.mol');

        $matches = SubstructureSearch::molfile($query)->matches();

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
            return StructureFactory::molfile(file_get_contents($structure))->create();
        });

        $query = file_get_contents(base_path().'/tests/Molfiles/1-propanol.mol');

        $matches = SubstructureSearch::molfile($query)->exact()->matches();

        $this->assertEquals(1, $matches->count());

        $this->assertTrue($matches->contains($structures['propanol']));
        $this->assertFalse($matches->contains($structures['propanediol']));
        $this->assertFalse($matches->contains($structures['isopropanol']));
    }
}
