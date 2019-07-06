<?php

namespace Nwogu\SmoothMigration\Abstracts;

use Illuminate\Support\Collection;
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
     * Auto Incrementing Id
     * @var bool
     */
    protected $autoIncrement = true;

    /**
     * Add Timestamps
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Add SoftDeletes
     * @var bool
     */
    protected $softdeletes = true;
    
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
        $allVars = get_object_vars($this);

        !$this->autoIncrement ?: $schemas["id"] = "increments";

        $baseschemas = Collection::make($allVars)->filter(function($vars, $key) {
            return !in_array($key, Constants::SCHEMA_DEFAULTS);
        })->all();

        $this->addLastDefaults($schemas, $baseschemas);

        return $schemas;
    }

    /**
     * Add Last Defaults To Schema
     * @param $schema
     * @return void
     */
    protected function addLastDefaults(&$schemas, $baseschemas)
    {
        $schemas = array_merge($schemas, $baseschemas);

        !$this->softdeletes ?: $schemas["softDeletes"] = "softDeletes";

        !$this->timestamps ?: $schemas["timestamps"] = "timestamps";
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
    public function hasChanged()
    {
        return $this->reader->hasChanged();
    }

    /**
     * Read Schema Changes
     * @return SchemaReader
     */
    public function readSchema()
    {
        $this->reader = new SchemaReader(
            $this->savedSchemaLoad(),
            $this->currentSchemaLoad()
        );

        return $this;
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
     * Returns Class Name without "Schema"
     * @return string
     */
    public function basename()
    {
        return substr(static::class, 0, 
                strpos(static::class, Constants::SMOOTH_SCHEMA_FILE));
    }
}