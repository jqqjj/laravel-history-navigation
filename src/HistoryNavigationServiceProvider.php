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
        $this->app->singleton('laravel_history_navigation', function ($app) {
            return new HistoryNavigation();
        });
    }

    public function provides()
    {
        return ['laravel_history_navigation'];
    }
}
