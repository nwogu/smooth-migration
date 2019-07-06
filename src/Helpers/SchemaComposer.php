<?php

namespace Nwogu\SmoothMigration\Helpers;

use Nwogu\SmoothMigration\Helpers\SchemaWriter;
use Nwogu\SmoothMigration\Helpers\Constants;

class SchemaComposer
{
    /**
     * Lines for Up method
     * @var array
     */
    protected $uplines = [];

    /**
     * Lines for foreign keys
     * @var array
     */
    protected $foreignLines = [];

    /**
     * Prepared Foreign Methods
     * @var array
     */
    protected $preparedForeign = [];

    /**
     * Lines for Down method
     * @var array
     */
    protected $downlines = [];

    /**
     * Renames
     * @var array
     */
    protected $renames = [];

    /**
     * Drops
     * @var array
     */
    protected $drops = [];

    /**
     * Additions
     * @var array
     */
    protected $additions = [];

    /**
     * Map Actions to Compose Methods
     * @var array
     */
    protected $composeMethods = [
        Constants::SCHEMA_CREATE_ACTION => "create",
        Constants::SCHEMA_UPDATE_ACTION => [
            "rename", "drop", "addition"
        ]
    ];

    /**
     * Schema Writer
     * @var SchemaWriter
     */
    protected $writer;

    /**
     * Construct
     * @param SchemaWriter $writer
     */
    public function __construct(SchemaWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Schema Composer Factory
     * @param SchemaWriter
     */
    public static function make(SchemaWriter $writer)
    {
        $self = new self($writer);

        return $self->init();
    }

    /**
     * Handle Schema Composition
     * @return void
     */
    public function init()
    {
        $composeMethod = $this->composeMethods[$this->writer->action()];

        if (method_exists($this, $composeMethod)) {
            return $this->$composeMethod();
        }
        foreach ($composeMethod as $callable) {
            return method_exists($this, $callable) ?: $this->$callable();
        }
    }

    /**
     * Replaces Stub holders with the appropriate values
     * @param string $action
     * @param string $className
     * @param string $table
     * @param string $upwriter
     * @return string
     */
    protected function populateStub($action, $className, $table, $upwriter)
    {
        $stub = file_get_contents(__DIR__ . "/stubs/$action.stub");

        $stub = str_replace(
            "DummyClass", $className, $stub);

        $stub = str_replace(
            "DummyTable", $table, $stub);

        $stub  = str_replace("UpWriter", $upwriter, $stub);

        return $stub;
    }

    /**
     * Compose a create table migration
     */
    protected function create()
    {
        foreach ($this->writer->schema->schemas() as $column => $schemas) {
            $this->composeUp($column, $schemas);
            if (! empty($this->preparedForeign)) {
                $this->composeUp($column, $this->preparedForeign, "foreignLines");
                $this->preparedForeign = [];
            }
        }
        $lines = array_merge_recursive($this->uplines, $this->foreignLines);

        return $this->populateStub(Constants::SCHEMA_CREATE_ACTION, 
            $this->writer->migrationClass(), $this->writer->schema->table(), 
            implode("\n\t\t\t", $lines));
    }

    /**
     * Compose up migration method
     */
    protected function composeUp($column, $schema, $linetype = "uplines")
    {
        $schema = is_array($schema) ? $schema : $this->schemaArray($schema, $column);
        $writer = "\$table->";
        foreach ($schema as $method => $options) {
            $writer = $this->writeLine($method, $options, $column, $writer);
        }
        $writer = $this->endWrite($writer);
        $this->$linetype[] = $writer;
    }

    /**
     * Write a migration method line
     * @return string
     */
    protected function writeLine(string $method, array $options, 
        string $column, string $writer = "\$table->") {
        if (! $this->doneFirst($writer)) {
            return $this->doFirst(
                $writer, $method, $column, $options);
        }
        return $writer . $this->doOther($method, $options);
    }

    /**
     * Check if first method has been chained
     * @param string
     * @return bool
     */
    protected function doneFirst(string $schema)
    {
        return strlen($schema) > 10;
    }

    /**
     * Chain first migration method method
     * @param string $upwriter
     * @param string $method
     * @param string $column
     * @param array $options
     * 
     * @return string
     */
    protected function doFirst(string $upwriter, string $method, string $column, array $options)
    {
        $column = $method == $column ? '' : $this->qualify($column);
        return $upwriter . $method . "($column" . $this->flatOptions($options, true);
    }

    /**
     * Chain other migration methods
     * @param string $method
     * @param array $options
     * 
     * @return string
     */
    protected function doOther(string $method, array $options)
    {
        return "->" . $method . "(" . $this->flatOptions($options);
    }

    /**
     * Qualifies a parameter method
     * @param mixed $param
     * @return $param
     */
    protected function qualify($param)
    {
        return is_numeric($param) || $this->isBool($param) ? $param: "'{$param}'";
    }

    /**
     * Checks if a string literal is boolean
     * @param mixed $param
     * @return bool
     */
    protected function isBool($param)
    {
        return strtolower($param) == "true" || strtolower($param) == "false";
    }

    /**
     * Compose options into migration method
     * @param array $options
     * @param bool $addComma
     * @return string
     */
    protected function flatOptions(array $options, $addComma = false)
    {
        if (empty($options)) return ")";

        $comma = $addComma ? ", " : "";

        $qoptions = array_map(function($option) {
            return $this->qualify($option);
        }, $options);

        return $comma . implode(",", $qoptions) . ")";
    }

    /**
     * End a writen line
     * @param string $writer
     * @return string $writer
     */
    protected function endWrite(string $writer)
    {
        return $writer . ";";
    }

    /**
     * Compose Schema To Array
     * @param string $schema
     * @param string $column
     * @return array $arrayed
     */
    protected function schemaArray(string $schema, string $column)
    {
        $arrayed = $this->arrayed($schema);

        if ($this->hasForeign($schema) && 
                !$this->hasReference($schema)) {
            $this->addReference($arrayed);
        }
        return $this->keySchema($arrayed, $column);

    }

    /**
     * Checks if a stringed schema has foreign
     * @param string $schema
     * @return bool
     */
    protected function hasForeign(string $schema)
    {
        return strpos($schema, "on=");
    }

    /**
     * Checks if a stringed schema contains reference
     * @param string $schema
     * @return bool
     */
    protected function hasReference(string $schema)
    {
        return strpos($schema, "references=");
    }

    /**
     * Checks if a stringed Schema has options
     * @param string $schema
     * @return bool
     */
    protected function hasOption(string $schema)
    {
        return strpos($schema, "=");
    }

    /**
     * Break a stringed schema into an arrayed schema
     * @param string $schema
     * @return array $arrayedSchema
     */
    protected function arrayed(string $schema)
    {
        return explode(",", $schema);
    }

    /**
     * Add reference to foreign
     * @param array $arrayed
     * @return void
     */
    protected function addReference(array $arrayed)
    {
        array_push($arrayed, "references=id");
    }

    /**
     * Key each schema methods to options
     * @param array $arrayed
     * @param string $column
     * @return array $keyedArray
     */
    protected function keySchema(array $arrayed, string $column)
    {
        foreach ($arrayed as $stringed) {
            $methodOptions = $this->getOptions($stringed);
                $keyedArray[$methodOptions[0]] = 
                    $methodOptions[1];
        }
        return $this->keyForeignSchema($keyedArray, $column);
    }

    /**
     * Get Method and Options from stringed schema
     * @param string $stringed
     * @return array $methodOptions
     */
    protected function getOptions(string $stringed)
    {
        if ($index = $this->hasOption($stringed)) {
            $options = explode(" ", trim(\substr($stringed, $index + 1)));
            $method = trim(\substr($stringed, 0, $index));
            return [$method, $options];
        }
        return [trim($stringed), []];
    }

    /**
     * Key schema that has foreign key to foreign methods
     * @param array $arrayed
     * @param string $column
     * @return array $foreignKeyedArray
     */
    protected function keyForeignSchema(array $arrayed, string $column)
    {
        if (array_key_exists("on", $arrayed)) {
            $this->prepareForeignKey($arrayed);
        }
        return $arrayed;
    }

    /**
     * Prepare foreign keys to be writen to line
     * @param array $arrayed
     * @return void
     */
    protected function prepareForeignKey(array &$arrayed)
    {
        foreach (Constants::FOREIGN_VALUES as $val) {
            $foreignArrayed[$val] = $arrayed[$val] ?? [];
            if (isset($arrayed[$val])) unset($arrayed[$val]);
        }
        ! empty($foreignArrayed["references"]) ?:
            $foreignArrayed["references"] = ["id"];
        if (isset($arrayed["onDelete"])) {
            $foreignArrayed["onDelete"] = $arrayed["onDelete"];
            unset($arrayed["onDelete"]);
        }
        $arrayed["unsigned"] = [];
        $this->preparedForeign =  $foreignArrayed ?? [];
    }
}