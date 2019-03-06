<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\Tests\Setup\BundleFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MoveProjectsTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_user_can_move_all_projects_to_another_bundle()
    {
        $this->withoutExceptionHandling();

        $user = factory('App\User')->create();
        $firstBundle = BundleFactory::ownedBy($user)->withProjects(2)->create();
        $secondBundle = BundleFactory::ownedBy($user)->withProjects(1)->create();

        $this->actingAs($user)->get("/bundle-projects/{$firstBundle->id}/edit")->assertStatus(200);
        $this->actingAs($user)->patch("/bundle-projects/{$firstBundle->id}", [
            'toBundle' => $secondBundle->id,
        ]);

        $this->assertcount(0, $firstBundle->fresh()->projects);
        $this->assertcount(3, $secondBundle->fresh()->projects);
    }
}
