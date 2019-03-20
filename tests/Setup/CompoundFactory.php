<?php

namespace Tests\Setup;

use App\User;
use App\Bundle;
use App\Project;
use App\Compound;

class CompoundFactory
{
    protected $molfile;

    protected $bundle;

    protected $project;

    protected $user;

    public function ownedBy($user)
    {
        $this->user = $user;

        return $this;
    }

    public function inBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    public function inProject($project)
    {
        $this->project = $project;

        return $this;
    }    

    public function withMolfile($molfile)
    {
        $this->molfile = $molfile;

        return $this;   
    }

    public function create($attributes = [])
    {
        $user = $this->user ?? factory(User::class)->create();

        $bundle = $this->bundle ?? factory(Bundle::class)->create(['user_id' => $user->id]);

        $project = $this->project ?? factory(Project::class)->create(['bundle_id' => $bundle->id]);

        $molfile = $this->molfile;

        $compound = factory(Compound::class)->create(array_merge($attributes, [
            'project_id'    => $project->id,
            'molfile'       => $molfile,
        ]));

        return $compound;
    }
}
