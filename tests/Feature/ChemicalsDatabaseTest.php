<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChemicalsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function the_database_returns_searches_by_name()
    {
        $this->signIn();

        $chemical = factory('App\Chemical')->create(['name' => 'benzoyl chloride']);

        $this->post('/database/search', [
            'search'     => 'benzoyl chloride',
        ])
            ->assertStatus(200)
            ->assertSee($chemical->name)
            ->assertSee($chemical->cas)
            ->assertSee($chemical->location);
    }

    /** @test **/
    public function the_database_returns_results_by_CAS_number()
    {
        $this->signIn();

        $chemical = factory('App\Chemical')->create(['cas' => '100-39-0']);

        $this->post('/database/search', [
            'search'     => '100-39-0',
        ])
            ->assertStatus(200)
            ->assertSee($chemical->name)
            ->assertSee($chemical->cas)
            ->assertSee($chemical->location);
    }

    /** @test **/
    public function the_database_returns_results_for_a_remark()
    {
        $this->signIn();

        $chemical = factory('App\Chemical')->create(['remarks' => 'homemade']);

        $this->post('/database/search', [
            'search'     => 'remark: homemade',
        ])
            ->assertStatus(200)
            ->assertSee($chemical->name)
            ->assertSee($chemical->cas)
            ->assertSee($chemical->location);
    }
}
