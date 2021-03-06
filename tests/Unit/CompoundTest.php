<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompoundTest extends TestCase
{
    use RefreshDatabase;

    protected $compound;

    public function setUp(): void
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

        $this->assertEquals(
            "/compounds/{$compound->id}",
            $compound->path());
    }
}
