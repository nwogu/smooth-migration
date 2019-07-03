<?php

namespace Nwogu\SmoothMigration\Helpers;

use Illuminate\Support\Str;
use Nwogu\SmoothMigration\Abstracts\Schema;
use Illuminate\Support\Facades\Schema as Builder;
use Nwogu\SmoothMigration\Helpers\SchemaComposer;
use Nwogu\SmoothMigration\Helpers\Constants;

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
        return  database_path("migrations/") .
                date('Y_m_d_His') . "_" . 
                Str::snake($this->migrationClass()) .
                ".php";
    }

    /**
     * Get Migration Class
     * @return string
     */
    public function migrationClass()
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
        return SchemaComposer::make($this);
    }

}