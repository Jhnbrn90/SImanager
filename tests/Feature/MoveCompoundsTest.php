<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MoveCompoundsTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_move_all_compounds_to_another_project()
    {
        $user = create('App\User');

        $defaultProject = $user->projects->first();

        $otherProject = ProjectFactory::ownedBy($user)->withCompounds(3)->create();

        $this->actingAs($user)->get("/project-compounds/{$otherProject->id}/edit")->assertStatus(200);

        $this->actingAs($user)->patch("/project-compounds/{$otherProject->id}", [
            'toProject' => $defaultProject->id,
        ]);

        $this->assertcount(3, $defaultProject->fresh()->compounds);
        $this->assertcount(0, $otherProject->fresh()->compounds);
    }

    /** @test **/
    public function a_user_can_not_move_projects_to_a_project_owned_by_another_user()
    {
        $john = factory('App\User')->create();
        $jane = factory('App\User')->create();

        $johnsProject = ProjectFactory::ownedBy($john)->withCompounds(3)->create();
        $janesProject = ProjectFactory::ownedBy($jane)->withCompounds(3)->create();

        $this->actingAs($john)->patch("/project-compounds/{$johnsProject->id}", [
            'toProject' => $janesProject->id,
        ])->assertStatus(403);

        $this->assertCount(3, $janesProject->fresh()->compounds);
        $this->assertCount(3, $johnsProject->fresh()->compounds);
    }
}
