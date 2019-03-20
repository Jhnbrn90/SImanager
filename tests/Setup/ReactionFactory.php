<?php

namespace Tests\Setup;

use App\User;
use App\Bundle;
use App\Project;
use App\Reaction;

class ReactionFactory
{
    protected $user;
    protected $bundle;
    protected $project;

    public function ownedBy($user)
    {
        $this->user = $user;

        return $this;
    }

    public function inBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    public function inProject($project)
    {
        $this->project = $project;
    }

    public function create()
    {
        $user = $this->user ?? factory(User::class)->create();

        $bundle = $this->bundle ?? factory(Bundle::class)->create(['user_id' => $user->id]);
        $project = factory(Project::class)->create(['bundle_id' => $bundle->id]);
        $reaction = factory(Reaction::class)->create(['project_id' => $project->id]);

        return $reaction;
    }
}
