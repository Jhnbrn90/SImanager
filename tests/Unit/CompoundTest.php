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
}
