<?php

namespace Nwogu\SmoothMigration\Abstracts;

use Illuminate\Support\Collection;
use Nwogu\SmoothMigration\Definition;
use Nwogu\SmoothMigration\Helpers\Constants;
use Nwogu\SmoothMigration\Helpers\SchemaReader;

abstract class Schema
{
    /**
     * Migration Table Name
     * @var string
     */
    protected $table;

    /**
     * Primary Key Id
     * @var string|null
     */
    protected $idField = "increments";

    /**
     * Add Timestamps
     * @var bool
     */
    protected $timestamps = true;
    
    /**
     * Specify SmoothSchema to run first.
     * @var array
     */
    protected $runFirst = [];

    /**
     * Get Table Name
     * @return string
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * Schema Reader
     * @var SchemaReader
     */
    protected $reader;

    /**
     * Get Schemas To Run First
     * @return array
     */
    public function runFirst()
    {
        return $this->runFirst;
    }

    /**
     * Get Table Schema
     * @return string
     */
    public function schemas()
    {
        $definition = new Definition;

        ! $this->idField ?: $definition->id = $this->idField;

        $this->define($definition);

        ! $this->timestamps ?: $definition->{Constants::TIMESTAMP} = Constants::TIMESTAMP;

        return get_object_vars($definition);
    }

    /**
     * Get Serialize Path For saved schemas
     * @return string
     */
    public function serializePath()
    {
        $serializerPath =  config("smooth.serializer_path") . static::class . ".json";
        if (! file_exists($serializerPath)) {
            throw new \Exception(
                "Serializer path $serializerPath not found for" . static::class);
        }
        return $serializerPath;
    }

    /**
     * Get Current Schema Load for Migration
     * @return array
     */
    public function currentSchemaLoad()
    {
        $serializeLoad['table'] = $this->table();
        
        $serializeLoad['schemas'] = $this->schemas();

        return $serializeLoad;
    }

    /**
     * Check if Migration Schema is Changed
     * @return bool
     */
    public function hasChanged()
    {
        return $this->reader->hasChanged();
    }

    /**
     * Get The active reader of Schema
     * @return SchemaReader
     */
    public function reader()
    {
        return $this->reader;
    }

    /**
     * Set the active reader
     * @param \Nwogu\SmoothMigration\Helpers\SchemaReader
     * 
     * @return void
     */
    public function setReader(SchemaReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Returns Class Name
     * @return string
     */
    public function className()
    {
        return static::class;
    }

    /**
     * Schema Definitions
     */
    abstract protected function define(Definition $definition);
}