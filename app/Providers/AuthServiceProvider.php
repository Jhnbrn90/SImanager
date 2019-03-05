<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        Gate::define('access-compounds', function ($user, $owner) {
            if (is_int($user)) {
                $user = User::findOrFail($user);
            }

            if ($user->id == $owner->id) {
                return true;
            }

            return $owner->supervisors->contains($user);
        });

        Gate::define('interact-with-compound', function ($user, $compound) {
            if ($user->id == $compound->user_id) {
                return true;
            }

            return $compound->user->supervisors->contains($user);
        });

        Gate::define('interact-with-project', function ($user, $project) {
            if ($user->id == $project->user_id) {
                return true;
            }

            return $project->user->supervisors->contains($user);
        });

        Gate::define('interact-with-reaction', function ($user, $reaction) {
            if ($user->id == $reaction->user_id) {
                return true;
            }

            return $reaction->user->supervisors->contains($user);
        });

    }
}
