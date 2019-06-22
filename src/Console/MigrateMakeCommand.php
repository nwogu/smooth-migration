<?php

namespace Nwogu\SmoothMigration\Console;

use Illuminate\Support\Composer;
use Illuminate\Support\Collection;
use Nwogu\SmoothMigration\Traits\SmoothMigratable;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseCommand;

class MigrateMakeCommand extends BaseCommand
{
    Use SmoothMigratable;

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
        {--smooth : Create a migration file from a Smooth Schema Class.}
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
     * Create Laravel's Default Migration Files
     * @return void
     */
    protected function writeSmoothMigration()
    {
        foreach ($this->getSchemaFiles() as $path) {
            $instance = $this->schemaInstance($path);

            if ($instance->schemaIsChanged()) {
                $this->writeNewSmoothMigration($instance);

                $this->createFile(
                    $this->serializerDirectory(),
                    $instance->serializePath(),
                    $this->fetchSerializableData($instance)
                );
            }
            
        }
    }

    /**
     * Get all of the smooth schema files in the smooth schema path.
     *
     * @return array
     */
    protected function getSchemaFiles()
    {
        return Collection::make($this->schemaDirectory())->flatMap(function ($path) {
            return $this->files->glob($path.'*Schema.php*');
        })->filter()->sortBy(function ($file) {
            return $this->getSchemaName($file);
        })->values()->all();
    }
}