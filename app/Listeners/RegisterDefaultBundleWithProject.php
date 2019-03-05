<?php

namespace App\Listeners;

use App\Bundle;
use App\Project;
use App\Events\UserWasCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterDefaultBundleWithProject
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserWasCreated  $event
     * @return void
     */
    public function handle(UserWasCreated $event)
    {
        $bundle = Bundle::create([
            'name'          => 'Default bundle',
            'description'   => 'Automatically generated bundle.',
            'user_id'       => $event->user->id,
        ]);

        $project = Project::create([
            'name'          => 'Default project',
            'description'   => 'Automatically generated project.',
            'user_id'       => $event->user->id,
            'bundle_id'     => $bundle->id,
        ]);
    }
}
