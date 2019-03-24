<?php

namespace App\Providers;

use App\Helpers\Checkmol;
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
        $this->app->bind('checkmol', function() {
            return new Checkmol();
        });
    }
}
