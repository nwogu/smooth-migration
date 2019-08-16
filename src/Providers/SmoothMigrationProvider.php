<?php

namespace Nwogu\SmoothMigration\Providers;

use Illuminate\Support\ServiceProvider;
use Nwogu\SmoothMigration\Helpers\Constants;
use Nwogu\SmoothMigration\Console\SmoothCreateCommand;
use Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository;

class SmoothMigrationProvider extends ServiceProvider
{

    /**
     * Register SmoothMigration Services
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SmoothMigrationRepository::class, function($app) {
            return new SmoothMigrationRepository($app["db"], Constants::SMOOTH_TABLE);
        });
    }

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