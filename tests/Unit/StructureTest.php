<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\Facades\StructureFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_structure_has_the_total_number_of_atoms()
    {
        $structure = factory('App\Structure')->create(['n_atoms' => 20]);
        $this->assertEquals(20, $structure->n_atoms);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_bonds()
    {
        $structure = factory('App\Structure')->create(['n_bonds' => 3]);
        $this->assertEquals(3, $structure->n_bonds);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_rings()
    {
        $structure = factory('App\Structure')->create(['n_rings' => 1]);
        $this->assertEquals(1, $structure->n_rings);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_carbon_atoms()
    {
        $structure = factory('App\Structure')->create(['n_C' => 6]);
        $this->assertEquals(6, $structure->n_C);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_hybridized_carbon_atoms()
    {
        $structure = factory('App\Structure')->create(['n_C1' => 1]);
        $this->assertEquals(1, $structure->n_C1);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_2_hybridized_carbon_atoms()
    {
        $structure = factory('App\Structure')->create(['n_C2' => 2]);
        $this->assertEquals(2, $structure->n_C2);
    }

    /** @test **/
    public function a_structure_the_number_of_carbon_atoms_with_at_least_1_bond_to_heteroatoms()
    {
        $structure = factory('App\Structure')->create(['n_CHB1p' => 2]);
        $this->assertEquals(2, $structure->n_CHB1p);
    }

    /** @test **/
    public function a_structure_has_the_number_of_carbon_atoms_with_at_least_2_bonds_to_heteroatoms()
    {
        $structure = factory('App\Structure')->create(['n_CHB2p' => 1]);
        $this->assertEquals(1, $structure->n_CHB2p);
    }

    /** @test **/
    public function a_structure_has_the_number_of_carbon_atoms_with_at_least_3_bonds_to_heteroatoms()
    {
        $structure = factory('App\Structure')->create(['n_CHB3p' => 1]);
        $this->assertEquals(1, $structure->n_CHB3p);
    }

    /** @test **/
    public function a_structure_has_the_number_of_carbon_atoms_with_4_bonds_to_hetereoatoms()
    {
        $structure = factory('App\Structure')->create(['n_CHB4' => 1]);
        $this->assertEquals(1, $structure->n_CHB4);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_2_hybridized_oxygen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_O2' => 1]);
        $this->assertEquals(1, $structure->n_O2);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_3_hybridized_oxygen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_O3' => 1]);
        $this->assertEquals(1, $structure->n_O3);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_hybridized_nitrogen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_N1' => 1]);
        $this->assertEquals(1, $structure->n_N1);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_2_hybridized_nitrogen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_N2' => 1]);
        $this->assertEquals(1, $structure->n_N2);
    }

    /** @test **/
    public function a_structure_has_the_number_of_sp_3_hybridized_nitrogen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_N3' => 1]);
        $this->assertEquals(1, $structure->n_N3);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_sulfur_atoms()
    {
        $structure = factory('App\Structure')->create(['n_S' => 2]);
        $this->assertEquals(2, $structure->n_S);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_selenium_and_tellurium_atoms()
    {
        $structure = factory('App\Structure')->create(['n_SeTe' => 1]);
        $this->assertEquals(1, $structure->n_SeTe);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_fluorine_atoms()
    {
        $structure = factory('App\Structure')->create(['n_F' => 3]);
        $this->assertEquals(3, $structure->n_F);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_chlorine_atoms()
    {
        $structure = factory('App\Structure')->create(['n_Cl' => 2]);
        $this->assertEquals(2, $structure->n_Cl);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_bromine_atoms()
    {
        $structure = factory('App\Structure')->create(['n_Br' => 5]);
        $this->assertEquals(5, $structure->n_Br);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_iodine_atoms()
    {
        $structure = factory('App\Structure')->create(['n_I' => 12]);
        $this->assertEquals(12, $structure->n_I);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_phosphorus_atoms()
    {
        $structure = factory('App\Structure')->create(['n_P' => 1]);
        $this->assertEquals(1, $structure->n_P);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_boron_atoms()
    {
        $structure = factory('App\Structure')->create(['n_B' => 1]);
        $this->assertEquals(1, $structure->n_B);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_metal_atoms()
    {
        $structure = factory('App\Structure')->create(['n_Met' => 1]);
        $this->assertEquals(1, $structure->n_Met);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_miscellaneous_atoms()
    {
        $structure = factory('App\Structure')->create(['n_X' => 1]);
        $this->assertEquals(1, $structure->n_X);
    }

    /** @test **/
    public function a_structure_has_the_number_of_single_bonds()
    {
        $structure = factory('App\Structure')->create(['n_b1' => 1]);
        $this->assertEquals(1, $structure->n_b1);
    }

    /** @test **/
    public function a_structure_has_the_number_of_double_bonds()
    {
        $structure = factory('App\Structure')->create(['n_b2' => 1]);
        $this->assertEquals(1, $structure->n_b2);
    }

    /** @test **/
    public function a_structure_has_the_number_of_triple_bonds()
    {
        $structure = factory('App\Structure')->create(['n_b3' => 1]);
        $this->assertEquals(1, $structure->n_b3);
    }

    /** @test **/
    public function a_structure_has_the_number_of_aromatic_bonds()
    {
        $structure = factory('App\Structure')->create(['n_bar' => 1]);
        $this->assertEquals(1, $structure->n_bar);
    }

    /** @test **/
    public function a_structure_has_the_number_of_C_O_single_bonds()
    {
        $structure = factory('App\Structure')->create(['n_C1O' => 1]);
        $this->assertEquals(1, $structure->n_C1O);
    }

    /** @test **/
    public function a_structure_has_the_number_of_C_O_double_bonds()
    {
        $structure = factory('App\Structure')->create(['n_C2O' => 1]);
        $this->assertEquals(1, $structure->n_C2O);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_C_N_bonds()
    {
        $structure = factory('App\Structure')->create(['n_CN' => 1]);
        $this->assertEquals(1, $structure->n_CN);
    }

    /** @test **/
    public function a_structure_has_the_total_number_of_hetero_atom_hetero_atom_bonds()
    {
        $structure = factory('App\Structure')->create(['n_XY' => 1]);
        $this->assertEquals(1, $structure->n_XY);
    }

    /** @test **/
    public function a_structure_has_the_number_of_3_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r3' => 1]);
        $this->assertEquals(1, $structure->n_r3);
    }

    /** @test **/
    public function a_structure_has_the_number_of_4_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r4' => 1]);
        $this->assertEquals(1, $structure->n_r4);
    }

    /** @test **/
    public function a_structure_has_the_number_of_5_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r5' => 1]);
        $this->assertEquals(1, $structure->n_r5);
    }

    /** @test **/
    public function a_structure_has_the_number_of_6_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r6' => 1]);
        $this->assertEquals(1, $structure->n_r6);
    }

    /** @test **/
    public function a_structure_has_the_number_of_7_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r7' => 1]);
        $this->assertEquals(1, $structure->n_r7);
    }

    /** @test **/
    public function a_structure_has_the_number_of_8_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r8' => 1]);
        $this->assertEquals(1, $structure->n_r8);
    }

    /** @test **/
    public function a_structure_has_the_number_of_9_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r9' => 1]);
        $this->assertEquals(1, $structure->n_r9);
    }

    /** @test **/
    public function a_structure_has_the_number_of_10_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r10' => 1]);
        $this->assertEquals(1, $structure->n_r10);
    }

    /** @test **/
    public function a_structure_has_the_number_of_11_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r11' => 1]);
        $this->assertEquals(1, $structure->n_r11);
    }

    /** @test **/
    public function a_structure_has_the_number_of_12_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r12' => 1]);
        $this->assertEquals(1, $structure->n_r12);
    }

    /** @test **/
    public function a_structure_has_the_number_of_13_or_more_membered_rings()
    {
        $structure = factory('App\Structure')->create(['n_r13p' => 1]);
        $this->assertEquals(1, $structure->n_r13p);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_any_nitrogen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rN' => 1]);
        $this->assertEquals(1, $structure->n_rN);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_1_nitrogen_atom()
    {
        $structure = factory('App\Structure')->create(['n_rN1' => 1]);
        $this->assertEquals(1, $structure->n_rN1);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_2_nitrogen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rN2' => 1]);
        $this->assertEquals(1, $structure->n_rN2);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_3_or_more_nitrogen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rN3p' => 1]);
        $this->assertEquals(1, $structure->n_rN3p);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_any_oxygen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rO' => 1]);
        $this->assertEquals(1, $structure->n_rO);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_1_oxygen_atom()
    {
        $structure = factory('App\Structure')->create(['n_rO1' => 1]);
        $this->assertEquals(1, $structure->n_rO1);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_2_or_more_oxygen_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rO2p' => 1]);
        $this->assertEquals(1, $structure->n_rO2p);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_any_sulfur_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rS' => 1]);
        $this->assertEquals(1, $structure->n_rS);
    }

    /** @test **/
    public function a_structure_has_the_number_of_rings_containing_any_hetero_atoms()
    {
        $structure = factory('App\Structure')->create(['n_rX' => 1]);
        $this->assertEquals(1, $structure->n_rX);
    }

    /** @test **/
    public function a_structure_has_the_number_of_aromatic_rings()
    {
        $structure = factory('App\Structure')->create(['n_rar' => 1]);
        $this->assertEquals(1, $structure->n_rar);
    }

    /** @test */
    public function a_structure_has_a_molfile()
    {
        $structure = factory('App\Structure')->create(['molfile' => 'fake molfile']);
        $this->assertEquals('fake molfile', $structure->molfile);
    }

    /** @test */
    public function a_structure_can_belong_to_a_chemical()
    {
        $chemical = factory('App\Chemical')->create(['name' => '2-fake-formaldehyde']);
        $structure = factory('App\Structure')->create();

        $chemical->structure()->save($structure);

        $this->assertEquals('2-fake-formaldehyde', $structure->structurable->name);
        $this->assertEquals($structure->id, $chemical->structure->id);
    }

    /** @test **/
    public function a_structure_has_a_SVG()
    {
        $svg = file_get_contents(base_path().'/tests/Svg/test.svg');

        $structure = create('App\Structure', ['svg' => $svg]);

        $this->assertEquals($svg, $structure->svg);
    }

    /** @test */
    public function a_structure_can_generate_and_save_its_svg_property()
    {
        $molfile = file_get_contents(base_path().'/tests/Molfiles/benzene.mol');

        $structure = StructureFactory::molfile($molfile)->create();

        $structure->saveSvg();

        $svg = file_get_contents(base_path().'/tests/Svg/benzene.svg');

        $this->assertEquals($svg, $structure->fresh()->svg);
    }
}
