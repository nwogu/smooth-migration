<?php

namespace Nwogu\SmoothMigration\Console;

use Exception;
use Illuminate\Support\Composer;
use Illuminate\Support\Collection;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Nwogu\SmoothMigration\Helpers\SchemaWriter;
use Nwogu\SmoothMigration\Traits\SmoothMigratable;
use Illuminate\Database\Migrations\MigrationCreator;
use Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseCommand;

class MigrateMakeCommand extends BaseCommand
{
    Use SmoothMigratable;

    /**
     * Ran Migrations
     * @var array
     */
    protected $ran = [];

    /**
     * Run Count
     * @var int
     */
    protected $runCount = 1;

    /**
     * SmoothMigratoionRepository
     * @var SmoothMigrationRepository
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     * @param \Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository $repository
     * @return void
     */
    public function __construct(
        MigrationCreator $creator, 
        Composer $composer, 
        SmoothMigrationRepository $repository
        )
    {
        $this->repository = $repository;

        $this->repository->setSource(config("database.default"));

        $this->makeFile();

        $this->modifySignature();
        
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

            $this->parentHandle();
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
        $this->prepareDatabase();

        foreach ($this->getSchemaFiles() as $path) {

            $instance = $this->schemaInstance($path);

            $this->runFirstMigrations($instance);  
            $this->writeNewSmoothMigration($instance);
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

    /**
     * Run First Migrations
     * @param Schema $schema
     * @return void
     */
    protected function runFirstMigrations(Schema $schema)
    {
        foreach ($schema->runFirst() as $firstRun) {

            $instance = $this->schemaInstance($firstRun);

            $schemaClass = get_class($schema);

            if (in_array($schemaClass, $instance->runFirst())) {
                $this->error(
                    "Circular Reference Error, {$schemaClass} specified to run first in $firstRun");
                    exit();
            }

            $this->writeNewSmoothMigration($instance);
        }
    }

    /**
     * Writes a migration to file
     * @param Schema $instance
     * @return void
     */
    protected function writeNewSmoothMigration(Schema $instance)
    {
        if (in_array($instance->basename(), $this->ran)) return;

        if ($instance->readSchema()->hasChanged()) {
            $this->info("Writing Migration For {$instance->basename()}");

            try {
                $this->writeSchema($instance); 
            }
            catch (\Exception $e) {
                $this->error($e->getMessage());
                exit();
            }

            $this->printChangeLog($instance->reader()->changelogs());

            $this->info("Migration Created Successfully");
            $this->info("Updating Serializer for {$instance->basename()}");

            $this->createFile(
                $this->serializerDirectory(),
                $instance->serializePath(),
                $this->fetchSerializableData($instance)
            );
            $this->info("Serializer Updated Successfully");
        } else {
            $this->info("No Schema Change Detected For {$instance->basename()}");
        }

        array_push($this->ran, $instance->basename());
    }

    /**
     * Write Migration File
     * @param Schema $schemaInstance
     * @return void
     */
    protected function writeSchema(Schema $schemaInstance)
    {
        $writer = new SchemaWriter($schemaInstance, $this->runCount);

        $this->createFile(
            $writer->migrationDirectory(),
            $writer->migrationPath(),
            $writer->write()
        );

        $this->runCount++;
    }

     /**
     * Modify Signature
     * @return void
     */
    protected function modifySignature()
    {
        $this->signature = str_replace("{name", "{name='*'", $this->signature);

        $this->signature .= "{--s|smooth : Create a migration file from a Smooth Schema Class.}";

        $this->signature .= "{--co|correct : Correct a migration file that has already run.}";
    }

    /**
     * Check for the name argument before calling parent
     * @return void
     */
    protected function parentHandle()
    {
        return $this->argument("name") == "'*'" ?

                $this->error("Name Argument is Required") :
                
                parent::handle();
    }

    /**
     * Print Change Logs To Terminal
     * @param array $changelogs
     * @return void
     */
    protected function printChangeLog(array $changelogs)
    {
        foreach ($changelogs as $log) {
            $this->info($log);
        }
    }

    /**
     * Prepare database to persist smooth migration info that has been run
     */
    protected function prepareDatabase()
    {
        if (! $this->repository->repositoryExists()) {
            $this->call(
                'smooth:install'
            );
        }
    }
}