<?php

namespace Nwogu\SmoothMigration\Providers;

use Illuminate\Support\ServiceProvider;
use Nwogu\SmoothMigration\Console\SmoothCreateCommand;

class SmoothMigrationProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SmoothCreateCommand::class,
            ]);
        }
        
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

}