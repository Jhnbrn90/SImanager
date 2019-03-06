<?php

namespace Tests\Setup;

use App\User;
use App\Project;
use App\Compound;

class ProjectFactory
{
    protected $compoundCount = 0;
    protected $user;

    public function ownedBy($user)
    {
        $this->user = $user;
        return $this;
    }

    public function withCompounds($count)
    {
        $this->compoundCount = $count;
        return $this;
    }

    public function create()
    {
        $user = $this->user ?? factory(User::class)->create();
        $project = factory(Project::class)->create(['user_id' => $user->id]);

        factory(Compound::class, $this->compoundCount)->create(['project_id' => $project]);

        return $project;
    }
}
