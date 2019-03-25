<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\Facades\StructureFactory;

class StructureFactoryTest extends TestCase
{
    /** @test **/
    public function it_can_read_properties_from_a_molfile()
    {
        $molfile = file_get_contents(base_path().'/tests/Molfiles/benzene.mol');
        $properties = [
            'n_atoms' => '6',
            'n_bonds' => '6',
            'n_rings' => '1',
            'n_C2' => '6',
            'n_C' => '6',
            'n_b2' => '1',
            'n_bar' => '6',
            'n_r6' => '1',
            'n_rar' => '1',
        ];

        $this->assertEquals($properties, StructureFactory::molfile($molfile)->properties());
    }

    /** @test **/
    public function it_can_read_properties_from_JS_draw_input()
    {
        $molfile = file_get_contents(base_path().'/tests/Molfiles/jsdraw/benzene.mol');
        $properties = [
            'n_atoms' => '6',
            'n_bonds' => '6',
            'n_rings' => '1',
            'n_C2' => '6',
            'n_C' => '6',
            'n_b2' => '1',
            'n_bar' => '6',
            'n_r6' => '1',
            'n_rar' => '1',
        ];

        $this->assertEquals($properties, StructureFactory::jsdraw($molfile)->properties());
    }
}
