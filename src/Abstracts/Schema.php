<?php

namespace Nwogu\SmoothMigration\Abstracts;

use Illuminate\Support\Collection;
use const Nwogu\SmoothMigration\Helpers\SCHEMA_DEFAULTS;
use const Nwogu\SmoothMigration\Helpers\SMOOTH_SCHEMA_FILE;
use Nwogu\SmoothMigration\Helpers\SchemaReader;

abstract class Schema
{
    /**
     * Migration Table Name
     * @var string
     */
    protected $table;

    /**
     * Auto Incrementing Id
     * @var bool
     */
    protected $autoIncrement = true;
    
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
        $allVars = get_object_vars($this);

        $schemas = Collection::make($allVars)->filter(function($vars, $key) {
            return !in_array($key, SCHEMA_DEFAULTS);
        })->all();

        !$this->autoIncrement ?: $schemas["id"] = "increments";
        
        return $schemas;
    }

    /**
     * Get Serialize Path For saved schemas
     * @return string
     */
    public function serializePath()
    {
        $serializerPath =  config("serializer_path") . __CLASS__ . ".json";
        if (! file_exists($serializerPath)) {
            throw new \Exception(
                "Serializer path $serializerPath not found for" . __CLASS__);
        }
        return $serializerPath;
    }

    /**
     * Get Saved Schema Load for Migration
     * @return array
     */
    public function savedSchemaLoad()
    {
        return json_decode(
            file_get_contents($this->serializePath()),
            true
        );
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
    public function schemaIsChanged()
    {
        return (new SchemaReader(
            $this->savedSchemaLoad(),
            $this->currentSchemaLoad()
        ))->hasChanged();
    }

    /**
     * Returns Class Name without "Schema"
     * @return string
     */
    public function basename()
    {
        return substr(static::class, 0, 
                strpos(static::class, SMOOTH_SCHEMA_FILE));
    }
}