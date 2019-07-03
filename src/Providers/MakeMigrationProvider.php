<?php

namespace Nwogu\SmoothMigration\Providers;

use Illuminate\Database\MigrationServiceProvider;
use Nwogu\SmoothMigration\Console\MigrateMakeCommand;

class MakeMigrationProvider extends MigrationServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerMigrateCommand();
    }

     /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('command.migrate.make', function ($app) {
            return new MigrateMakeCommand($this->app->make('migrator'), $this->app->make('composer'));
        });
    }

}