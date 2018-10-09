<?php

namespace Tests\Feature;

use App\Project;
use App\Compound;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function a_project_can_get_all_of_its_compounds()
    {
        // Given we have a project 
        $project = factory(Project::class)->create(['name' => "Fake Project"]);

        // Given we have some compounds
        $compounds = factory(Compound::class, 2)->create(['project_id' => $project]);
        $compoundFromDifferentProject = factory(Compound::class)->create();

        $this->assertTrue($project->compounds->contains($compounds[0]));
        $this->assertTrue($project->compounds->contains($compounds[1]));
        
        $this->assertFalse($project->compounds->contains($compoundFromDifferentProject));
    }
}
