<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChemicalsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function the_database_returns_searches_by_name()
    {
        $this->withoutExceptionHandling();

        $this->signIn();

        $chemical = factory('App\Chemical')->create(['name' => 'bezyl chloride']);

        $this->post('/database/search', [
            'query'     => 'benzyl chloride',
        ])
            ->assertStatus(200)
            ->assertSee($chemical->name);
    }
}
