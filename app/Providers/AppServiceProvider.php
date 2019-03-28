<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('checkmol', 'App\Helpers\Checkmol');
        $this->app->bind('matchmol', 'App\Helpers\Matchmol');
        $this->app->bind('structure-factory', 'App\Helpers\StructureFactory');
    }
}
