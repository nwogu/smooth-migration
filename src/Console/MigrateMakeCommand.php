<?php

namespace Nwogu\SmoothMigration\Console;

use Illuminate\Support\Str;
use Illuminate\Support\Composer;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseCommand;

class MigrateMakeCommand extends BaseCommand
{

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        $this->signature .= "
        {--smooth : Create a migration file from a Smooth Migration Class.}
        ";

        parent::__construct($creator, $composer);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->useSmoothMigrator() ?

            $this->writeSmoothMigration() :

            parent::handle();
    }

    /**
     * Determine if a smooth migration should be created.
     *
     * @return bool
     */
    protected function useSmoothMigrator()
    {
        return $this->input->hasOption('smooth') && $this->option('smooth');
    }

    /**
     * Create a Smooth Migration Class
     * @return void
     */
    protected function writeSmoothMigration()
    {

    }
}