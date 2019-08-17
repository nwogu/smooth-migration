<?php

namespace Nwogu\SmoothMigration\Console;

use Exception;
use Illuminate\Support\Composer;
use Illuminate\Support\Collection;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Nwogu\SmoothMigration\Helpers\Constants;
use Nwogu\SmoothMigration\Helpers\SchemaReader;
use Nwogu\SmoothMigration\Helpers\SchemaWriter;
use Nwogu\SmoothMigration\Traits\SmoothMigratable;
use Illuminate\Database\Migrations\MigrationCreator;
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
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        $this->makeDependencies();

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
     * Determine if a smooth migration should be corrected.
     * @param Schema $schema
     *
     * @return bool
     */
    protected function shouldCorrectMigration(Schema $schema)
    {
        return !$this->hasCorrectionBeenRequested()
                ?: $this->isCorrectionForSchema($schema);
    }

    /**
     * Determine if a smooth migration correction has been requested.
     *
     * @return bool
     */
    protected function hasCorrectionBeenRequested()
    {
        return $this->input->hasOption('correct') && $this->option('correct');
    }

    /**
     * Determine if a corection is meant for a schema.
     *
     * @return bool
     */
    protected function isCorrectionForSchema(Schema $schema)
    {
        return $this->parseCorrectionOption() == "all" 
            || $schema->className() == $this->transformOptionToSchemaClassName();
    }

    /**
     * Parse option for correction
     * @return string $table
     */
    protected function parseCorrectionOption()
    {
        return explode(".", $this->option('correct'))[0];
    }

    /**
     * Transform table to schema class name
     * @return string
     */
    protected function transformOptionToSchemaClassName()
    {
        return \Illuminate\Support\Str::studly(
            $this->parseCorrectionOption() 
            . Constants::SMOOTH_SCHEMA_FILE
        );
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

            $instance = $this->schemaInstance($firstRun);

            $schemaClass = $schema->className();

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
        if (in_array($instance->className(), $this->ran)) return;

        $schemaClass = $instance->className();

        $this->readSchema($instance);

        if ($instance->hasChanged()) {
            $this->info("Writing Migration For {$schemaClass}");

            try {
                $migrationPath = $this->writeSchema($instance); 
            }
            catch (\Exception $e) {
                $this->error($e->getMessage());
                exit();
            }

            $this->printChangeLog($instance->reader()->changelogs());

            $this->info("Migration for {$schemaClass} Created Successfully");
            $this->info("Updating Log...");

            $this->repository->log(
                $schemaClass,
                $this->fetchSerializableData($instance),
                $migrationPath,
                $this->repository->getNextBatchNumber(
                    $schemaClass
                )
            );
            
            $this->info("Log for {$schemaClass} Updated Successfully");
        } else {
            $this->info("No Schema Change Detected For {$schemaClass}");
        }

        array_push($this->ran, $instance->className());
    }

    /**
     * Read Schema Changes
     * @param Schema $instance
     * @return void
     */
    protected function readSchema(Schema $instance)
    {
        $previousSchemaload = $this->repository->previousSchemaLoad(
            $instance->className(),
            $this->getBatch($instance));
            
        $schemaReader = new SchemaReader(
            $previousSchemaload, $instance->currentSchemaLoad());
        $instance->setReader($schemaReader);
    }

    /**
     * Get smooth migration batch
     * @param Schema $instance
     * @return int $batch
     */
    protected function getBatch(Schema $instance)
    {
        return $this->shouldCorrectMigration($instance) 
            ? $this->repository->reduceBatchNumber($instance) 
            : $this->repository->getLastBatchNumber($instance);
    }

    /**
     * Parse batch number from option
     * @return int $batch
     */
    protected function parseBatchOption()
    {
        return explode(".", $this->option('correct'))[1] ?? 1;
    }

    /**
     * Reduce batch number
     * @param Schema $instance
     * @return int
     */
    protected function reduceBatchNumber(Schema $instance)
    {
        $batchNumber = $this->repository->getLastBatchNumber($instance) - 
                        $this->parseBatchOption();
        return $batchNumber < 0 ? 1 : $batchNumber;
    }

    /**
     * Write Migration File
     * @param Schema $schemaInstance
     * @return string $migrationPath
     */
    protected function writeSchema(Schema $schemaInstance)
    {
        $writer = new SchemaWriter($schemaInstance, $this->runCount);

        $schemaClass = $schemaInstance->className();

        $migrationPath = $this->shouldCorrectMigration($schemaInstance) 
            ? $this->repository->getLastMigration(
                $schemaClass)
            : $writer->migrationPath();

        $this->createFile(
            $writer->migrationDirectory(),
            $migrationPath,
            $writer->write()
        );

        $this->runCount++;

        return $migrationPath;
    }

     /**
     * Modify Signature
     * @return void
     */
    protected function modifySignature()
    {
        $this->signature = str_replace("{name", "{name='*'", $this->signature);

        $this->signature .= "{--s|smooth : Create a migration file from a Smooth Schema Class.}";

        $this->signature .= "{--co|correct=all : Correct a migration file that has already run.}";
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

}