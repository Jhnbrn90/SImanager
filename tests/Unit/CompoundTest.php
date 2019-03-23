<?php

namespace Tests\Unit;

use Tests\TestCase;
use Facades\Tests\Setup\CompoundFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();

        $this->compound = create('App\Compound');
        $this->structure = file_get_contents('tests/stubs/structure.php');
    }

    /** @test **/
    public function a_compound_has_an_owner()
    {
        $this->assertInstanceOf('App\User', $this->compound->owner);
    }

    /** @test **/
    public function a_compound_belongs_to_a_project()
    {
        $project = create('App\Project', ['name' => 'Fake Project 007']);
        $compound = create('App\Compound', ['project_id' => $project]);

        $this->assertEquals('Fake Project 007', $compound->project->name);
    }

    /** @test **/
    public function a_compound_can_generate_its_path()
    {
        $compound = create('App\Compound');

        $this->assertEquals("/compounds/{$compound->id}", $compound->path());
    }

    /** @test **/
    public function a_compound_can_prepare_its_molfile_and_svg_assets()
    {
        $compound = CompoundFactory::withMolfile($this->structure)->create(['id' => 9999999]);

        $compound->toMolfile();
        $compound->toSVG();

        $this->assertFileExists($pathToMolfile = storage_path()."/app/public/molfiles/{$compound->id}.mol");
        $this->assertFileExists($pathToSvg = storage_path()."/app/public/svg/{$compound->id}.svg");
        $this->assertContains("storage/svg/{$compound->id}.svg", $compound->SVGPath);

        unlink($pathToMolfile);
        unlink($pathToSvg);
    }

    /** @test **/
    public function a_compound_returns_a_standard_svg_if_it_does_not_have_a_structure()
    {
        $compound = CompoundFactory::create(['id' => 9999999]);

        $compound->toSVG();
        $this->assertFileNotExists($pathToSvg = storage_path()."/app/public/svg/{$compound->id}.svg");

        $this->assertEquals('storage/svg/unknown.svg', $compound->SVGPath);
    }

    /** @test **/
    public function in_lack_of_a_molfile_a_compound_will_create_a_molfile_from_its_structure()
    {
        $compound = CompoundFactory::withMolfile($this->structure)->create(['id' => 9999999]);

        $this->assertFileNotExists($pathToMolfile = storage_path()."/app/public/molfiles/{$compound->id}.mol");

        $compound->toSVG();

        $this->assertFileExists($pathToMolfile = storage_path()."/app/public/molfiles/{$compound->id}.mol");
        $this->assertFileExists($pathToSVG = storage_path()."/app/public/svg/{$compound->id}.svg");

        unlink($pathToMolfile);
        unlink($pathToSVG);
    }

    /** @test **/
    public function a_compound_can_return_the_number_of_protons_in_its_formula_and_NMR_data()
    {
        $compound = create('App\Compound', [
         'formula'     => 'C18H21NO3',
         'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).',
       ]);

        $this->assertEquals(21, $compound->formulaProtons);
        $this->assertEquals(21, $compound->nmrProtons);
    }

    /** @test **/
    public function a_compound_can_check_if_its_proton_NMR_data_matches_its_formula()
    {
        $compound = create('App\Compound', [
         'formula'     => 'C18H21NO3',
         'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).',
       ]);

        $incorrectCompound = create('App\Compound', [
         'formula'     => 'C18H20NO3',
         'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).',
       ]);

        $this->assertTrue($compound->checkProtonNMR());
        $this->assertFalse($incorrectCompound->checkProtonNMR());
    }

    /** @test **/
    public function a_compound_can_return_the_number_of_carbons_in_its_formula_and_NMR_data()
    {
        $compound = create('App\Compound', [
         'formula'     => 'C12H19BrO2',
         'C_NMR_data'  => '13C NMR (126 MHz, CDCl3) δ 140.21, 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66.',
       ]);

        $this->assertEquals(12, $compound->formulaCarbons);
        $this->assertEquals(12, $compound->nmrCarbons);
    }

    /** @test **/
    public function a_compound_can_check_if_its_carbon_NMR_data_matches_its_formula()
    {
        $compound = create('App\Compound', [
         'formula'     => 'C12H19BrO2',
         'C_NMR_data'  => '13C NMR (126 MHz, CDCl3) δ 140.21, 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66.',
       ]);

        $this->assertTrue($compound->checkCarbonNMR());
    }

    /** @test **/
    public function a_compound_can_format_its_formula()
    {
        $compound = factory('App\Compound')->create(['formula' => 'C2H6O']);
        $this->assertEquals('C<sub>2</sub>H<sub>6</sub>O', $compound->formattedFormula);

        $compound = factory('App\Compound')->create(['formula' => 'CHCl3']);
        $this->assertEquals('CHCl<sub>3</sub>', $compound->formattedFormula);
    }

    /** @test **/
    public function a_compound_can_format_the_molecular_formula_for_the_rotation_solvent()
    {
        $compound = factory('App\Compound')->create(['alpha_solvent' => 'C2H6O']);
        $this->assertEquals('C<sub>2</sub>H<sub>6</sub>O', $compound->formattedAlphaSolvent());

        $compound = factory('App\Compound')->create(['alpha_solvent' => 'CHCl3']);
        $this->assertEquals('CHCl<sub>3</sub>', $compound->formattedAlphaSolvent());
    }

    /** @test **/
    public function a_compound_can_format_its_formula_with_sodium_mass_adduct()
    {
        $compound = factory('App\Compound')->create([
          'mass_adduct' => 'Na+',
          'formula' => 'C6H12O6',
        ]);

        $this->assertEquals(
          'C<sub>6</sub>H<sub>12</sub>O<sub>6</sub>Na [M+Na]<sup>+</sup>',
          $compound->formattedFormulaForHRMS()
        );
    }

    /** @test **/
    public function a_compound_can_format_its_formula_with_proton_mass_adduct()
    {
        $compound = factory('App\Compound')->create([
          'mass_adduct' => 'H+',
          'formula' => 'C6H12O6',
        ]);

        $this->assertEquals(
          'C<sub>6</sub>H<sub>13</sub>O<sub>6</sub> [M+H]<sup>+</sup>',
          $compound->formattedFormulaForHRMS()
        );
    }

    /** @test **/
    public function a_compound_can_format_its_formula_with_hydride_mass_adduct()
    {
        $compound = factory('App\Compound')->create([
          'mass_adduct' => 'H-',
          'formula' => 'C6H12O6',
        ]);

        $this->assertEquals(
          'C<sub>6</sub>H<sub>11</sub>O<sub>6</sub> [M-H]<sup>+</sup>',
          $compound->formattedFormulaForHRMS()
        );
    }
}
