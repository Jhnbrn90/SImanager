<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\ChemicalFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubstructureSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function guests_can_not_search_by_substructure()
    {
        $this->get('/database/substructure')->assertRedirect('/login');
        $this->post('/database/substructure/search')->assertRedirect('/login');
    }

    /** @test **/
    public function a_user_finds_all_matches_for_a_queried_substructure()
    {
        $this->signIn();
        
        ChemicalFactory::named('1-benzene')->withStructure($this->molfile('benzene'))->create();
        ChemicalFactory::named('chloro-benzene')->withStructure($this->molfile('chlorobenzene'))->create();


        $this->post('/database/substructure/search', [
            'molfile'   => $this->molfile('benzene'),
        ])->assertSee('1-benzene')
        ->assertSee('chloro-benzene');
    }

    // /** @test **/
    // public function a_user_finds_an_exact_match_for_a_queried_substructure()
    // {
    //     $this->signIn();

    //     ChemicalFactory::named('1-benzene')->withStructure($this->testMolfile('benzene'))->create();
    //     ChemicalFactory::named('chloro-benzene')->withStructure($this->testMolfile('chlorobenzene'))->create();

    //     $this->post('/database/substructure/search', [
    //         'molfile'   => $this->testMolfile('benzene'),
    //         'exact'     => 'checked',
    //     ])->assertSee('1-benzene')->assertDontSee('chloro-benzene');
    // }

    protected function molfile($name)
    {
        return file_get_contents(base_path()."/tests/Molfiles/{$name}.mol");
    }
}
