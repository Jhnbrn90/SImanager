<?php

namespace App\Listeners;

use App\Events\UserWasCreated;
use App\Project;

class RegisterNewProject
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
     * @param UserWasCreated $event
     *
     * @return void
     */
    public function handle(UserWasCreated $event)
    {
        Project::create([
            'name'          => 'Default project',
            'description'   => 'Automatically generated project.',
            'user_id'       => $event->user->id,
        ]);
    }
}
