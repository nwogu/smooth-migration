<?php

namespace Nwogu\SmoothMigration\Traits;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Nwogu\SmoothMigration\Helpers\Constants;
use Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository;

/**
 * SmoothMigratable
 */
trait SmoothMigratable
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The Smooth Migration Repository
     * 
     * @var \Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository
     */
    protected $repository;

    /**
     * Create Filesytem Instance.
     *
     * @return  \Illuminate\Filesystem\Filesystem  $files
     */
    public function makeFile()
    {
        $this->files = app()->make(Filesystem::class);
    }

    /**
     * Resolve an instance of Smooth Migration Repository
     * 
     * @return \Nwogu\SmoothMigration\Repositories\SmoothMigrationRepository
     */
    public function makeRepository()
    {
        $this->repository = app()->make(SmoothMigrationRepository::class);
        $this->prepareDatabase();
    }

    /**
     * Initialize neccesary dependency properties
     * 
     * @return void
     */
    public function makeDependencies()
    {
        $this->makeFile();
        $this->makeRepository();
    }

    /**
     * Get Smooth Serializer Directiory
     * 
     * @return string
     */
    protected function serializerDirectory()
    {
        return config("smooth.serializer_path", $this->laravel->databasePath() .
            DIRECTORY_SEPARATOR . Constants::SMOOTH_SERIALIZER_FOLDER . DIRECTORY_SEPARATOR);
    }

    /**
     * Get Smooth Schema Directory
     * 
     * @return string
     */
    protected function schemaDirectory()
    {
        return config("smooth.schema_path", $this->laravel->databasePath() .
                DIRECTORY_SEPARATOR . Constants::SMOOTH_SCHEMA_FOLDER . DIRECTORY_SEPARATOR);
    }

    /**
     * Get the name of the Schema.
     *
     * @param  string  $path
     * @return string
     */
    public function getSchemaName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Get Instance of a smooth Schema class
     * @param string $path
     * @param bool $namespaced
     * @return Schema
     */
    protected function schemaInstance($path)
    {
        $class = $this->getSchemaName($path);

        $path = $this->getSchemaFullPath($path);

        $this->files->requireOnce($path);

        return new $class;
    }

    /**
     * Get Schema Full Path
     * @param string $path
     */
    protected function getSchemaFullPath(string $path)
    {
        $isFullPath = strpos($path, ".php");

        return $isFullPath ? $path : $this->schemaDirectory() . $path . ".php";
    }

    /**
     * Fetch Serializable Data from Schema Class
     * @param $schemaClass
     * @return json
     */
    protected function fetchSerializableData($schemaClass)
    {
        if (! $schemaClass instanceof Schema) {
            $schemaClass = $this->schemaInstance($schemaClass);
        }

        return json_encode(
            $schemaClass->currentSchemaLoad());
    }

    /**
     * Handle Smooth File Creations
     * @param string $directory
     * @param string $file
     * @param string $data
     */
    protected function createFile($directory, $file, $data)
    {
        if (! $this->files->exists($directory)) {
                $this->files->makeDirectory(
                    $directory);
        }
        $this->files->put($file, $data);
    }

    /**
     * Prepare database to persist smooth migration info that has been run
     */
    protected function prepareDatabase()
    {
        if (! $this->repository->repositoryExists()) {
            Artisan::call(
                'smooth:install'
            );
        }
    }
    
}
