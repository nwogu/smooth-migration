<?php

namespace Nwogu\SmoothMigration\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use const Nwogu\SmoothMigration\Helpers\SMOOTH_MIGRATION_FILE;
use const Nwogu\SmoothMigration\Helpers\SMOOTH_MIGRATION_FOLDER;
use const Nwogu\SmoothMigration\Helpers\SMOOTH_SERIALIZER_FOLDER;

class SmoothCreateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'smooth:create {table}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smooth:create {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Smooth Migration Class for a table';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new smooth migration class.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer    $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createSmoothMigration(
            $this->smoothMigrationDirectory(), 
            $this->smoothMigrationPath(), $this->populateStub());

        $this->info('Smooth Migration Class created successfully!');

        $this->info('Creating Smooth Migration Serializer.....');

        $this->createSmoothMigration(
            $this->smoothSerializerDirectory(),
            $this->smoothSerializerPath(), $this->fetchSerializableData()
        );

        $this->info('Smooth Migration Serializer created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Get Smooth Serializer Path.
     *
     * @return string
     */
    protected function smoothSerializerPath()
    {
        return $this->smoothSerializerDirectory() .
                $this->getClassName() .
                SMOOTH_MIGRATION_FILE . "json";
    }

    /**
     * Get Smooth Migration Path.
     *
     * @return string
     */
    protected function smoothMigrationPath()
    {
        return $this->smoothMigrationDirectory() .
                $this->getClassName() .
                SMOOTH_MIGRATION_FILE . "php";
    }

    /**
     * Get Smooth Serializer Directiory
     * 
     * @return string
     */
    protected function smoothSerializerDirectory()
    {
        return config("smooth.serializer_path", $this->smoothMigrationDirectory() .
                SMOOTH_SERIALIZER_FOLDER . DIRECTORY_SEPARATOR);
    }

    /**
     * Get Smooth Migration Directory
     * 
     * @return string
     */
    protected function smoothMigrationDirectory()
    {
        return config("smooth.migration_path", $this->laravel->databasePath() .
                DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR .
                SMOOTH_MIGRATION_FOLDER . DIRECTORY_SEPARATOR);
    }

    /**
     * Gets Smooth Migration Class Name
     */
    protected function getClassName()
    {
        return Str::studly($this->argument('table'));
    }

    /**
     * Replaces Stub holders with the appropriate values
     * @return string
     */
    protected function populateStub()
    {
        $stub = $this->files->get(__DIR__ . "/stubs/SmoothMigration.stub");

        $stub = str_replace(
            "{{SMOOTH_MIGRATION_CLASS}}", $this->getClassName() . SMOOTH_MIGRATION_FILE, $stub);

        $stub = str_replace(
            "{{TABLE_NAME}}", $this->argument('table'), $stub);

        return $stub;
    }

    /**
     * Fetch Serializable Data from Migration Class
     * 
     * @return json
     */
    protected function fetchSerializableData()
    {
        $smoothMigrationModel = $this->getClassName() . 
            SMOOTH_MIGRATION_FILE;

        $serializeLoad = [];

        $this->files->requireOnce($this->smoothMigrationPath());

        $smoothMigrationModel = new $smoothMigrationModel;
        $serializeLoad['table'] = $smoothMigrationModel->getTable();
        $serializeLoad['columns'] = $smoothMigrationModel->getColumns();
        $serializeLoad['schemas'] = $smoothMigrationModel->getSchemas();

        return json_encode($serializeLoad);
    }

    /**
     * Handle Smooth File Creations
     * @param string $directory
     * @param string $file
     * @param string $data
     */
    protected function createSmoothMigration($directory, $file, $data)
    {
        if (! $this->files->exists($directory)) {
                $this->files->makeDirectory(
                    $directory);
        }
        $this->files->put($file, $data);
    }

}