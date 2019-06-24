<?php

namespace Nwogu\SmoothMigration\Helpers;

use Illuminate\Support\Str;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Illuminate\Support\Facades\Schema as Builder;
use const Nwogu\SmoothMigration\Helpers\SCHEMA_CREATE_ACTION;
use const Nwogu\SmoothMigration\Helpers\SCHEMA_UPDATE_ACTION;

class SchemaWriter
{
    /**
     * Schema Instance
     * @var Schema $schema
     */
    protected $schema;

    /**
     * Writer Table Action
     * @var string $action
     */
    protected $action;

    /**
     * Construct
     * @var Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->setaction();
    }

    /**
     * Sets action for shema writing
     * @return string
     */
    protected function setaction()
    {
        $this->action = Builder::exists($this->schema->table()) ? 
            SCHEMA_UPDATE_ACTION :
            SCHEMA_CREATE_ACTION ;
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
    protected function migrationPath()
    {
        return  $this->laravel->databasePath().'/migrations/' .
                date('Y_m_d_His') . "_" . 
                Str::snake($this->migrationClass()) .
                ".php";
    }

    /**
     * Get Migration Class
     * @return string
     */
    protected function migrationClass()
    {
        return Str::studly($this->schema->basename() . "Table");
    }

    /**
     * Call write action method
     * @return void
     * @throws \Exception
     */
    public function write()
    {
        $method = "write" . Str::studly($this->action) . "Action";

        if (method_exists($this, $method)) {

            $this->$method();
        }

        throw new \Exception("Migration Method {$this->action} not supported");
    }

    /**
     * handles write action for creating new migrations
     * @return void
     */
    protected function writeCreateAction()
    {

    }

}