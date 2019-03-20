<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChemicalTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_chemical_has_a_name()
    {
        $chemical = factory('App\Chemical')->create(['name' => 'Fake chemical']);
        $this->assertEquals('Fake chemical', $chemical->name);
    }

    /** @test **/
    public function a_chemical_has_a_structure_id()
    {
        $chemical = factory('App\Chemical')->create(['structure_id' => 33]);
        $this->assertEquals(33, $chemical->structure_id);
    }

    /** @test **/
    public function a_chemical_has_a_CAS_number()
    {
        $chemical = factory('App\Chemical')->create(['cas' => '100-39-0']);
        $this->assertEquals('100-39-0', $chemical->cas);
    }

    /** @test **/
    public function a_chemical_has_a_molecular_weight()
    {
        $chemical = factory('App\Chemical')->create(['molweight' => '100.1010']);
        $this->assertEquals('100.1010', $chemical->molweight);
    }

    /** @test **/
    public function a_chemical_has_a_density()
    {
        $chemical = factory('App\Chemical')->create(['density' => '1.034']);
        $this->assertEquals('1.034', $chemical->density);
    }

    /** @test **/
    public function a_chemical_has_a_quantity()
    {
        $chemical = factory('App\Chemical')->create(['quantity' => '500 g']);
        $this->assertEquals('500 g', $chemical->quantity);
    }

    /** @test **/
    public function a_chemical_has_a_lab_location()
    {
        $chemical = factory('App\Chemical')->create(['location' => '4W35']);
        $this->assertEquals('4W35', $chemical->location);
    }

    /** @test **/
    public function a_chemical_has_a_cabinet_number()
    {
        $chemical = factory('App\Chemical')->create(['cabinet' => '12']);
        $this->assertEquals('12', $chemical->cabinet);
    }

    /** @test **/
    public function a_chemical_has_a_number()
    {
        $chemical = factory('App\Chemical')->create(['number' => 80]);
        $this->assertEquals(80, $chemical->number);
    }

    /** @test **/
    public function a_chemical_can_have_a_remark()
    {
        $chemical = factory('App\Chemical')->create(['remarks' => 'fake chemical']);
        $this->assertEquals('fake chemical', $chemical->remarks);
    }
}
