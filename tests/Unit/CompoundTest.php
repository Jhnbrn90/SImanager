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


}
