<?php

namespace Nwogu\SmoothMigration\Helpers;

use Illuminate\Support\Str;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Illuminate\Support\Facades\Schema as IlluminateSchema;
use Nwogu\SmoothMigration\Helpers\SchemaComposer;
use Nwogu\SmoothMigration\Helpers\Constants;
use Carbon\Carbon;

class SchemaWriter
{
    /**
     * Schema Instance
     * @var Schema $schema
     */
    public $schema;

    /**
     * Writer Table Action
     * @var string $action
     */
    protected $action;

    /**
     * Migration Class Name
     * @var string
     */
    protected $migrationClass;

    /**
     * Run Count
     * @var int
     */
    protected $runCount;

    /**
     * Construct
     * @param Schema $schema
     * @param int $runCount
     */
    public function __construct(Schema $schema, int $runCount)
    {
        $this->schema = $schema;
        $this->runCount = $runCount;
        $this->setaction();
        $this->makeMigrationClass();
    }

    /**
     * Sets action for shema writing
     * @return string
     */
    protected function setaction()
    {
        $this->action = IlluminateSchema::hasTable($this->schema->table()) ? 
            Constants::SCHEMA_UPDATE_ACTION :
            Constants::SCHEMA_CREATE_ACTION ;
    }

    /**
     * Return Current Action
     * @return string
     */
    public function action()
    {
        return $this->action;
    }

    /**
     * Get Migration Path
     * @return string
     */
    public function migrationPath()
    {
        return  $this->migrationDirectory() .
                date('Y_m_d_His') . $this->runCount . "_" . 
                Str::snake($this->migrationClass()) .
                ".php";
    }

    /**
     * Get Migration Directory
     * @return string
     */
    public function migrationDirectory()
    {
        return  database_path("migrations/");
    }

    /**
     * Get Migration Class
     * @return string
     */
    public function migrationClass()
    {
        return $this->migrationClass;
    }

    /**
     * Form a migration class
     * @param string $class
     * @return void
     */
    public function makeMigrationClass($class = null)
    {
        $this->migrationClass = $class ?? Str::studly($this->schema->className() . "MigrationOn" . 
            Carbon::now()->format("DMYhis"));
    }

    /**
     * Call write action method
     * @return void
     * @throws \Exception
     */
    public function write()
    {
        return SchemaComposer::make($this);
    }

}