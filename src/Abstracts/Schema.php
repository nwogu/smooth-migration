<?php

namespace Nwogu\SmoothMigration\Abstracts;

use Illuminate\Support\Collection;

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
    protected function getTable()
    {
        return $this->table;
    }

    /**
     * Get Table Schema
     * @return string
     */
    protected function getSchemas()
    {
        $allVars = get_object_vars($this);
        $schemas = Collection::make($allVars)->filter(function($vars, $key) {
            return !in_array($key, ["table", "runFirst", "autoIncrement"]);
        })->all();
        if ($this->autoIncrement) $schemas["id"] = "increments";
        return $schemas;
    }

    /**
     * Get Serialize Path For saved schemas
     * @return string
     */
    protected function serializePath()
    {
        return config("serializer_path") . __CLASS__ . ".json";
    }

    /**
     * Get Saved Schema Load for Migration
     * @return string
     */
    protected function savedSchemaLoad()
    {
        return json_decode(
            file_get_contents($this->serializePath()),
            true
        );
    }

    /**
     * Get Current Schema Load for Migration
     * @return string
     */
    protected function currentSchemaLoad()
    {
        $serializeLoad['table'] = $this->getTable();
        
        $serializeLoad['schemas'] = $this->getSchemas();

        return $serializeLoad;
    }

    /**
     * Check if Migration Schema is Changed
     * @return bool
     */
    protected function schemaIsChanged()
    {

    }
}