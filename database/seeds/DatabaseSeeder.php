<?php

use App\User;
use App\Bundle;
use App\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {   
        $users = User::all();

        $users->each(function($user) {
            // Create a default bundle for each user
            $bundle = factory(Bundle::class)->create([
                'user_id'       => $user->id,
                'name'          => 'Default bundle',
                'description'   => 'Automatically generated bundle.',
            ]);

            // check if the user already has projects 
            if ($user->projects->count() > 0) {
                $user->projects->each(function ($project) use ($bundle) {
                    $project->bundle_id = $bundle->id;
                    $project->save();
                });
            } else {
                $project = factory(Project::class)->create([
                    'user_id'           => $user->id, 
                    'name'              => 'Default project',
                    'description'       => 'Automatically generated project.',
                    'bundle_id'         => $bundle->id,
                ]);
            }

        });
    }
}
