<?php

namespace Jqqjj\LaravelHistoryNavigation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class HistoryNavigationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {

    }

    public function register()
    {
        $this->app->singleton('laravel.history.navigation', HistoryNavigation::class);
    }

    public function provides()
    {
        return ['laravel.history.navigation'];
    }
}
