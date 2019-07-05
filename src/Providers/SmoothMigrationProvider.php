<?php

namespace Nwogu\SmoothMigration\Providers;

use Illuminate\Support\ServiceProvider;
use Nwogu\SmoothMigration\Console\MigrateMakeCommand;
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

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMigrateMakeCommand();
    }

     /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('command.migrate.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

}