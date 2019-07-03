<?php

namespace Nwogu\SmoothMigration\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Nwogu\SmoothMigration\Traits\SmoothMigratable;
use Nwogu\SmoothMigration\Helpers\Constants;

class SmoothCreateCommand extends Command
{
    Use SmoothMigratable;

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
    protected $description = 'Create a Smooth Schema Class for a table';

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new smooth schema class.
     *
     * @param  \Illuminate\Support\Composer    $composer
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;

        $this->makeFile();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createFile(
            $this->schemaDirectory(), 
            $this->schemaPath(), $this->populateStub());

        $this->info('Schema Class created successfully!');

        $this->info('Creating Schema Serializer...');

        $this->createFile(
            $this->serializerDirectory(),
            $this->serializerPath(), $this->fetchSerializableData(
                $this->schemaPath()
            )
        );

        $this->info('Schema Serializer created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Get Smooth Serializer Path.
     *
     * @return string
     */
    protected function serializerPath()
    {
        return $this->serializerDirectory() .
                $this->getClassName() .
                Constants::SMOOTH_SCHEMA_FILE . ".json";
    }

    /**
     * Get Smooth Schema Path.
     *
     * @return string
     */
    protected function schemaPath()
    {
        return $this->schemaDirectory() .
                $this->getClassName() .
                Constants::SMOOTH_SCHEMA_FILE . ".php";
    }

    /**
     * Gets Smooth Schema Class Name
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
        $stub = $this->files->get(dump(__DIR__ . "/stubs/Schema.stub"));

        $stub = str_replace(
            "{{SMOOTH_SCHEMA_CLASS}}", $this->getClassName() . Constants::SMOOTH_SCHEMA_FILE, $stub);

        $stub = str_replace(
            "{{TABLE_NAME}}", $this->argument('table'), $stub);

        return $stub;
    }

}