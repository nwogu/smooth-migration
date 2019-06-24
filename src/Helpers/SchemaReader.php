<?php

namespace Nwogu\SmoothMigration\Helpers;

class SchemaReader
{
    /**
     * Previous Schemas
     * @var array
     */
    protected $previousSchemas = [];

    /**
     * Previous Table
     * @var string
     */
    protected $previousTable;

    /**
     * Current Table
     * @var string
     */
    protected $currentTable;

    /**
     * Current Schemas
     * @var array
     */
    protected $currentSchemas = [];

    /**
     * Change Logs
     * @var array
     */
    protected $changelogs = [];

    /**
     * Rename Changes
     */
    protected $renames = [];

    /**
     * Column Changes
     * @var array
     */
    protected $changes = [];

    /**
     * Addition Changes
     * @var array
     */
    protected $addtions = [];

    /**
     * Drop Changes
     * @var array
     */
    protected $drops = [];

    /**
     * Check if schema has changed
     * @var bool
     */
    protected $hasChanged = false;

    /**
     * Previous Columns
     * @var array
     */
    protected $previousColumns = [];

    /**
     * Current Columns
     * @var array
     */
    protected $currentColumns = [];

    /**
     * Construct
     * @param array $previousSchemaLoad
     * @param array $currentSchemaLoad
     */
    public function __construct(array $previousSchemaLoad, array $currentSchemaLoad)
    {
        $this->previousTable = $previousSchemaLoad["table"];
        $this->currentTable = $currentSchemaLoad["table"];
        $this->previousSchemas = array_values($previousSchemaLoad["schemas"]);
        $this->currentSchemas = array_values($currentSchemaLoad["schemas"]);
        $this->previousColumns = array_keys($previousSchemaLoad["schemas"]);
        $this->currentColumns = array_keys($currentSchemaLoad["schemas"]);
        $this->read();
    }

    /**
     * Read schema and checks for changes
     * @return bool
     */
    protected function read()
    {
        if ($this->previousTable != $this->currentTable) {
            return $this->hasChanged = true;
        }
        if (count($this->previousColumns) != count($this->currentColumns)) {
            return $this->hasChanged = true;
        }
        $this->readByColumn();
    }

    /**
     * Read Schema By Columns
     * @return bool
     */
    protected function readByColumn($index = 0)
    {
        if (!$this->hasChanged && $index < count($this->previousColumns)) {
            if ($this->previousColumns[$index] != $this->currentColumns[$index]) {
                return $this->hasChanged = true;
            }
            $this->readByColumn($index++);
        }
        $this->readBySchema();
    }

    /**
     * Read Schema by Schema
     * @return bool
     */
    protected function readBySchema($index = 0)
    {
        $schemaisDifferent = function ($previous, $current) {
            if (empty(array_diff($previous, $current)) && 
                empty(array_diff($current, $previous))) {
                return false;
            }
            return true;
        };
        if (!$this->hasChanged && $index < count($this->previousSchemas)) {
            $previousSchemaArray = $this->schemaToArray($this->previousSchemas[$index]);
            $currentSchemaArray = $this->schemaToArray($this->currentSchemas[$index]);
            if ($schemaisDifferent($previousSchemaArray, $currentSchemaArray)) {
                return $this->hasChanged = true;
            }
            $this->readBySchema($index++);

        }
    }

    /**
     * Get Array Representation of stringed Schema
     * @param string $schema
     * @return array
     */
    protected function schemaToArray($schema)
    {
        $hasForeign = function ($schema) {
            return strpos($schema, "on=");
        };

        $hasReference = function ($schema) {
            return strpos($schema, "references=");
        };

        $hasOptions = function ($schema) {
            return strpos($schema, "=");
        };

        $getOptions = function ($schema) use ($hasOptions){
            if ($index = $hasOptions($schema)) {
                $options = explode(" ", trim(\substr($schema, $index + 1)));
                $method = trim(\substr($schema, 0, $index));
                array_push($options, $method);
                return $options;
            }
            return [trim($schema)];
        };

        $arrayedSchema = explode("," , $schema);

        $finalSchema = [];

        if ($hasForeign($schema)) {
            if (! $hasReference($schema)) {
                array_push($arrayedSchema, "references=id");
            }
        }

        foreach ($arrayedSchema as $arraySchema) {
            $finalSchema = array_merge_recursive($finalSchema, $getOptions($arraySchema));
        }

        return $finalSchema;
    }

    /**
     * Reads Change value
     * @return bool
     */
    public function hasChanged()
    {
        return $this->hasChanged;
    }

}