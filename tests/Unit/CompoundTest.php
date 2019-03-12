<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    protected $compound;

    public function setUp()
    {
        parent::setUp();

        $this->compound = create('App\Compound');
    }

    /** @test **/
    public function a_compound_has_a_creator()
    {
        $this->assertInstanceOf('App\User', $this->compound->creator);
    }

    /** @test **/
    public function a_compound_can_generate_its_path()
    {
        $compound = create('App\Compound');

        $this->assertEquals("/compounds/{$compound->id}", $compound->path());
    }

    /** @test **/
    public function a_molfile_is_created_when_a_new_compound_is_added_to_the_database()
    {
        $compound = create('App\Compound');

        $structure = "C3H6O
APtclcactv12201710073D 0   0.00000     0.00000

 10  9  0  0  0  0  0  0  0  0999 V2000
    1.3051   -0.6772   -0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    0.0000    0.0763   -0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
   -0.0000    1.2839    0.0000 O   0  0  0  0  0  0  0  0  0  0  0  0
   -1.3051   -0.6772    0.0000 C   0  0  0  0  0  0  0  0  0  0  0  0
    1.6198   -0.8588    1.0277 H   0  0  0  0  0  0  0  0  0  0  0  0
    1.1748   -1.6296   -0.5138 H   0  0  0  0  0  0  0  0  0  0  0  0
    2.0647   -0.0881   -0.5138 H   0  0  0  0  0  0  0  0  0  0  0  0
   -1.1059   -1.7488   -0.0000 H   0  0  0  0  0  0  0  0  0  0  0  0
   -1.8767   -0.4138    0.8900 H   0  0  0  0  0  0  0  0  0  0  0  0
   -1.8767   -0.4138   -0.8900 H   0  0  0  0  0  0  0  0  0  0  0  0
  1  2  1  0  0  0  0
  2  3  2  0  0  0  0
  2  4  1  0  0  0  0
  1  5  1  0  0  0  0
  1  6  1  0  0  0  0
  1  7  1  0  0  0  0
  4  8  1  0  0  0  0
  4  9  1  0  0  0  0
  4 10  1  0  0  0  0
M  END
$$$$
";

        $compound->toMolfile($structure);

        $this->assertFileExists(storage_path() . "/app/public/molfiles/{$compound->id}.mol");
    }

    /** @test **/
    public function an_svg_image_is_prepared_when_a_new_compound_is_added_to_the_database()
    {
        $compound = create('App\Compound');

        $compound->toSVG();

        $this->assertFileExists(storage_path() . "/app/public/svg/{$compound->id}.svg");
    }

    /** @test **/
    public function a_compound_returns_the_number_of_protons_in_its_formula_and_NMR_data()
    {  
       $compound = create('App\Compound', [
         'formula'     => 'C18H21NO3',
         'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).'
       ]);

       $this->assertEquals(21, $compound->formulaProtons);
       $this->assertEquals(21, $compound->nmrProtons);
    }

    /** @test **/
    public function a_compound_can_check_the_integrity_of_its_proton_NMR_data()
    {
       $compound = create('App\Compound', [
         'formula'     => 'C18H21NO3',
         'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).'
       ]);

       $incorrectCompound = create('App\Compound', [
         'formula'     => 'C18H20NO3',
         'H_NMR_data'  => '1H NMR (600 MHz, CDCl3) δ 7.17 – 7.13 (m, 2H), 6.89 – 6.84 (m, 2H), 6.02 – 5.92 (m, 1H), 5.26 – 5.11 (m, 2H), 4.34 (dd, J = 98.0, 14.5 Hz, 2H), 3.81 (s, 3H), 3.25 – 3.15 (m, 2H), 2.55 (s, 3H), 2.01 (d, J = 9.6 Hz, 1H), 1.40 (s, 3H).'
       ]);

       $this->assertTrue($compound->checkProtonNMR());   
       $this->assertFalse($incorrectCompound->checkProtonNMR());   
    }

    /** @test **/
    public function a_compound_returns_the_number_of_carbons_in_its_formula_and_NMR_data()
    {  
       $compound = create('App\Compound', [
         'formula'     => 'C12H19BrO2',
         'C_NMR_data'  => '13C NMR (126 MHz, CDCl3) δ 140.21, 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66.'
       ]);

       $this->assertEquals(12, $compound->formulaCarbons);
       $this->assertEquals(12, $compound->nmrCarbons);
    }

       /** @test **/
    public function a_compound_can_check_the_integrity_of_its_carbon_NMR_data()
    {
       $compound = create('App\Compound', [
         'formula'     => 'C12H19BrO2',
         'C_NMR_data'  => '13C NMR (126 MHz, CDCl3) δ 140.21, 137.13, 128.69, 110.30, 72.69, 68.99, 40.08, 36.65, 35.77, 25.44, 24.29, 24.22, 15.66.'
       ]);
       
       $this->assertTrue($compound->checkCarbonNMR());   
    }

    /** @test **/
    public function a_compound_belongs_to_a_project()
    {
       $project = create('App\Project', ['name' => 'Fake Project 007']);
       $compound = create('App\Compound', ['project_id' => $project]);

       $this->assertEquals('Fake Project 007', $compound->project->name);
    }
}
