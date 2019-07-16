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

        $this->publishes([
            base_path('/vendor/nwogu/smooth-migration/config/smooth.php')  => config_path('smooth.php')
        ], 'smooth-config');
        
    }

}