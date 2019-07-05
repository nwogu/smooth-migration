<?php

namespace Nwogu\SmoothMigration\Console;

use Illuminate\Support\Composer;
use Illuminate\Support\Collection;
use Nwogu\SmoothMigration\Traits\SmoothMigratable;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseCommand;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Nwogu\SmoothMigration\Helpers\SchemaWriter;

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

            $instance = $this->schemaInstance($firstRun, true);

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
        if ($instance->readSchema()->hasChanged()) {
            $this->info("Writing Migration For {$instance->basename()}");

            $this->writeSchema($instance);

            $this->info("Migration Created Successfully");
            $this->info("Updating Serializer for {$instance->basename()}");

            $this->createFile(
                $this->serializerDirectory(),
                $instance->serializePath(),
                $this->fetchSerializableData($instance)
            );
            $this->info("Serializer Updated Successfully");
        }
        $this->info("No Schema Change Detected For {$instance->basename()}");
    }

    /**
     * Write Migration File
     * @param Schema $schemaInstance
     * @return void
     */
    protected function writeSchema(Schema $schemaInstance)
    {
        $writer = new SchemaWriter($schemaInstance);

        $this->createFile(
            $writer->migrationDirectory(),
            $writer->migrationPath(),
            $writer->load()
        );
    }

     /**
     * Modify Signature
     * @return void
     */
    protected function modifySignature()
    {
        $this->signature = str_replace("{name", "{name=''", $this->signature);

        $this->signature .= "{--smooth : Create a migration file from a Smooth Schema Class.}";
    }

    /**
     * Check for the name argument before calling parent
     * @return void
     */
    protected function parentHandle()
    {
        return empty($this->argument("name")) ?

                $this->error("Name Argument is Required") :
                
                parent::handle();
    }
}