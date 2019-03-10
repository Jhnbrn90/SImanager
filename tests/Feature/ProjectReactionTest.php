<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
   public function a_reaction_belongs_to_a_project()
   {
      $project = create('App\Project', ['name' => 'Fake Project 007']);
      $reaction = create('App\Reaction', ['project_id' => $project->id]);

      $this->assertEquals('Fake Project 007', $reaction->project->name);
   }
}
