<?php

use App\Project;
use App\User;
use Illuminate\Database\Seeder;

class UserProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();

        $users->each(function ($user) {
            $project = factory(Project::class)->create([
                'user_id'       => $user->id,
                'name'          => 'Default project',
                'description'   => 'Automatically generated project.',
            ]);

            $user->compounds->each(function ($compound) use ($project) {
                $compound->project_id = $project->id;
                $compound->save();
            });
        });
    }
}
