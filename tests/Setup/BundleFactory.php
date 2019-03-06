<?php

namespace Tests\Setup;

use App\User;
use App\Bundle;
use App\Project;

class BundleFactory
{
    protected $projectCount = 0;

    protected $user;

    public function withProjects($count)
    {
        $this->projectCount = $count;
        return $this;
    }

    public function ownedBy($user)
    {
        $this->user = $user;
        return $this;
    }

    public function create()
    {
        $user = $this->user ?? factory(User::class)->create();
        
        $bundle = factory(Bundle::class)->create(['user_id' => $user]);

        factory(Project::class, $this->projectCount)->create([
            'user_id'   => $user,
            'bundle_id' => $bundle,
        ]);

        return $bundle;
    }
}
