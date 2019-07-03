<?php

namespace Nwogu\SmoothMigration\Traits;

use Illuminate\Filesystem\Filesystem;
use Nwogu\SmoothMigration\Abstracts\Schema;
use const Nwogu\SmoothMigration\Helpers\SMOOTH_SCHEMA_FOLDER;
use const Nwogu\SmoothMigration\Helpers\SMOOTH_SERIALIZER_FOLDER;

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
     * Create a new smooth Schema class.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Get Smooth Serializer Directiory
     * 
     * @return string
     */
    protected function serializerDirectory()
    {
        return config("smooth.serializer_path", $this->schemaDirectory() .
                SMOOTH_SERIALIZER_FOLDER . DIRECTORY_SEPARATOR);
    }

    /**
     * Get Smooth Schema Directory
     * 
     * @return string
     */
    protected function schemaDirectory()
    {
        return config("smooth.schema_path", $this->laravel->databasePath() .
                DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR .
                SMOOTH_SCHEMA_FOLDER . DIRECTORY_SEPARATOR);
    }

    /**
     * Get the name of the Schema.
     *
     * @param  string  $path
     * @return string
     */
    protected function getSchemaName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Get Instance of a smooth Schema class
     * @param string $path
     * @param bool $namespaced
     * @return Schema
     */
    protected function schemaInstance($path, $namespaced = false)
    {
        $class = $this->getSchemaName($path);

        $namespaced ?: $this->files->requireOnce($path);

        return new $class;
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
    
}
