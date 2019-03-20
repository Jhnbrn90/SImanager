<?php

namespace Tests\Unit;

use Tests\TestCase;
use Facades\Tests\Setup\ReactionFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_reaction_has_a_solvent()
    {
        $reaction = factory('App\Reaction')->create(['solvent_id' => 1]);

        $this->assertEquals(1, $reaction->solvent_id);
    }

    /** @test **/
    public function a_reaction_has_an_owner()
    {
        $user = create('App\User');
        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->assertTrue($user->is($reaction->owner));
    }

    /** @test **/
    public function a_reaction_may_belong_to_many_starting_materials()
    {
        $reaction = factory('App\Reaction')->create();
        $compound = factory('App\Compound')->create();

        $reaction->addStartingMaterial($compound);

        $this->assertEquals($compound->fresh()->id, $reaction->startingMaterials->first()->id);

        $anotherCompound = factory('App\Compound')->create();
        $reaction->addStartingMaterial($anotherCompound);

        $this->assertCount(2, $reaction->startingMaterials);
        $this->assertTrue($reaction->startingMaterials->contains($anotherCompound));
    }

    /** @test **/
    public function a_reaction_may_belongs_to_many_reagents()
    {
        $reaction = factory('App\Reaction')->create();
        $compound = factory('App\Compound')->create();

        $reaction->addReagent($compound);

        $this->assertEquals($compound->fresh()->id, $reaction->reagents->first()->id);

        $anotherCompound = factory('App\Compound')->create();
        $reaction->addReagent($anotherCompound);

        $this->assertCount(2, $reaction->reagents);
        $this->assertTrue($reaction->reagents->contains($anotherCompound));
    }

    /** @test **/
    public function a_reaction_may_belongs_to_many_products()
    {
        $reaction = factory('App\Reaction')->create();
        $compound = factory('App\Compound')->create();

        $reaction->addProduct($compound);

        $this->assertEquals($compound->fresh()->id, $reaction->products->first()->id);

        $anotherCompound = factory('App\Compound')->create();
        $reaction->addProduct($anotherCompound);

        $this->assertCount(2, $reaction->products);
        $this->assertTrue($reaction->products->contains($anotherCompound));
    }

    /** @test **/
    public function a_reaction_can_fetch_its_path()
    {
        $reaction = factory('App\Reaction')->create();
        $this->assertEquals('/reactions/' . $reaction->id, $reaction->path());
    }

    /** @test **/
    public function a_reaction_belongs_to_a_project()
    {
        $reaction = factory('App\Reaction')->create(['project_id' => 2]);
        $this->assertEquals(2, $reaction->project_id);
    }

    /** @test **/
    public function a_reaction_can_generate_the_correct_product_label()
    {
        $user = factory('App\User');
        $reaction = factory('App\Reaction')->create(['label' => 'JBN_2']);

        $this->assertCount(0, $reaction->products);

        $this->assertEquals('JBN_2a', $reaction->nextProductLabel());
        
        $compound = factory('App\Compound')->create();
        $reaction->addProduct($compound);        
        
        $this->assertEquals('JBN_2b', $reaction->nextProductLabel());
    }

    /** @test **/
    public function a_new_reaction_increments_the_label_code()
    {
        $user = create('App\User', ['prefix' => 'JBN']);

        $reaction = ReactionFactory::ownedBy($user)->create();

        $this->assertEquals('JBN_2', $user->newReactionLabel);
    }
}
