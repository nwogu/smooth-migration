<?php

namespace Nwogu\SmoothMigration\Abstracts;

use Illuminate\Support\Str;

abstract class SmoothMigration
{
    /**
     * Migration Table Name
     * @var string
     */
    protected $table;

    /**
     * Migration Columns
     * @var array
     */
    protected $columns = [];

    /**
     * Specify SmoothMigration to run first.
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
     * Get Table Columns
     * @return string
     */
    protected function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get Table Schema
     * @return string
     */
    protected function getSchemas()
    {
        $schemas = [];
        array_map(function ($column) use (&$schemas){
            $method = "get" . Str::studly($column) . "Schema";
            if (method_exists($this, $method)) {
                $schemas[$column] = $method();
            }
        }, $this->columns);
    }
}