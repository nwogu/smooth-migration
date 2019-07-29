<?php

namespace Nwogu\SmoothMigration\Helpers;

use Closure;
use Nwogu\SmoothMigration\Helpers\Constants;
use Nwogu\SmoothMigration\Helpers\SchemaReader;
use Nwogu\SmoothMigration\Helpers\SchemaWriter;

class SchemaComposer
{
    /**
     * @var SchemaReader
     */
    protected $reader;
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
        Constants::SCHEMA_CREATE_ACTION => Constants::SCHEMA_CREATE_ACTION,
        Constants::SCHEMA_UPDATE_ACTION => [
            Constants::DEF_CHANGE_UP_ACTION, Constants::COLUMN_ADD_UP_ACTION, 
            Constants::FOREIGN_DROP_UP_ACTION, Constants::FOREIGN_ADD_UP_ACTION, 
            Constants::DROP_MORPH_UP_ACTION, Constants::COLUMN_DROP_UP_ACTION, 
            Constants::COLUMN_RENAME_UP_ACTION,
            Constants::DEF_CHANGE_DOWN_ACTION, Constants::COLUMN_ADD_DOWN_ACTION, 
            Constants::FOREIGN_ADD_DOWN_ACTION, Constants::FOREIGN_DROP_DOWN_ACTION, 
            Constants::DROP_MORPH_DOWN_ACTION, Constants::COLUMN_DROP_DOWN_ACTION, 
            Constants::COLUMN_RENAME_DOWN_ACTION,
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

        $this->reader = $this->writer->schema->reader();
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

        if (is_string($composeMethod)) {
            $this->$composeMethod();
        } else {
            foreach ($composeMethod as $callable) {
                $this->$callable();
            }
        }
        return $this->finalize();
    }

    /**
     * Finish Composition
     * @return string
     */
    protected function finalize()
    {
        $lines = array_merge_recursive($this->uplines, $this->foreignLines);

        $downlines = $this->downlines ? 
            implode("\n\t\t\t", $this->downlines) :
            '';

        return $this->populateStub($this->writer->action(), 
            $this->writer->migrationClass(), $this->writer->schema->table(), 
            implode("\n\t\t\t", $lines), $downlines);
    }

    /**
     * Replaces Stub holders with the appropriate values
     * @param string $action
     * @param string $className
     * @param string $table
     * @param string $upwriter
     * @return string
     */
    protected function populateStub($action, $className, $table, $upwriter, $downwriter)
    {
        $stub = file_get_contents(__DIR__ . "/stubs/$action.stub");

        $stub = str_replace(
            "DummyClass", $className, $stub);

        $stub = str_replace(
            "DummyTable", $table, $stub);

        $stub  = str_replace("UpWriter", $upwriter, $stub);

        $stub = $action == Constants::SCHEMA_CREATE_ACTION ?
            $stub : str_replace("DownWriter", $downwriter, $stub);

        return $stub;
    }

    /**
     * Compose a create table migration
     */
    protected function create()
    {
        foreach ($this->writer->schema->schemas() as $column => $schemas) {
            $this->compose($column, $schemas);
            $this->composeForeign($column);
        }
    }

    /**
     * Handle Column Definition changes for Up Method
     * @return void
     */
    protected function defChangeUp()
    {
        foreach ($this->reader->defChanges() as $previous => $column) {
            $this->compose(
                $column, $this->reader->currentLoad()[$column],
                "uplines", false, $this->afterWrite());
            }
    }

    /**
     * Handle Column Definition changes for Down Method
     * @return void
     */
    protected function defChangeDown()
    {
        foreach ($this->reader->defChanges() as $previous => $column) {
            $this->compose(
                $column, $this->reader->previousLoad()[$previous],
                "downlines", false, $this->afterWrite());
            }
    }

    /**
     * Get Callback for action to perform after writing
     * @return Closure
     */
    protected function afterWrite()
    {
        return function ($line, $lineType) {
            $writer = $this->endWrite($line . $this->doOther("change"));
            $this->$lineType[] = $writer;
        };
    }

    /**
     * Handle Column Drops For Up Method
     * @return void
     */
    protected function columnDropUp()
    {
        foreach ($this->reader->columnDrops() as $column) {
            $dropSchema = $this->columnDropSchema($column);
            $this->compose(
                key($dropSchema), $dropSchema);
        }
    }

    /**
     * Handle Column Drops for down method
     * @return void
     */
    protected function columnDropDown()
    {
        foreach ($this->reader->columnDrops() as $column) {
            $this->compose(
                $column, $this->reader->previousLoad()[$column],
                "downlines");
            $this->composeForeign($column, "downlines");
        }
    }

    /**
     * Handle Column Additions for up method
     * @return void
     */
    protected function columnAddUp()
    {
        foreach ($this->reader->columnAdds() as $column) {
            $this->compose(
                $column, $this->reader->currentLoad()[$column]);
        }
    }

    /**
     * Handle Column Additions for down method
     * @return void
     */
    protected function columnAddDown()
    {
        foreach ($this->reader->columnAdds() as $column) {
            $dropSchema = $this->columnDropSchema($column);
            $this->compose(
                key($dropSchema), $dropSchema,
                "downlines");
        }
    }

     /**
     * Handle Column Rename Action for Up Method
     * @return void
     */
    protected function columnRenameUp()
    {
        foreach ($this->reader->columnRenames() as $previous => $current) {
            $this->compose(
                $previous, $this->columnRenameSchema($current));
        }
    }

    /**
     * Handle Column Rename Action for Down Method
     * @return void
     */
    protected function columnRenameDown()
    {
        foreach ($this->reader->columnRenames() as $previous => $current) {
            $this->compose(
                $current, $this->columnRenameSchema($previous), "downlines");
        }
    }

    /**
     * Handle Foreign key Additions For Up Method
     * @return void
     */
    protected function addForeignUp()
    {
        foreach ($this->reader->addForeigns() as $column) {
            $this->schemaArray($this->reader->currentLoad()[$column], $column);
            $this->composeForeign($column);
        }
        
    }

    /**
     * Handle Foreign key Additions for down method
     * @return void
     */
    protected function addForeignDown()
    {
        foreach ($this->reader->addForeigns() as $column) {
            $foreignDropSchema = $this->foreignDropSchema($column);
            $this->compose(key($foreignDropSchema), $foreignDropSchema, "downlines");
        }
        
    }

    /**
     * Handle Drop of Foreign keys for up method
     * @return void
     */
    protected function dropForeignUp()
    {
        foreach ($this->reader->dropForeigns() as $column) {
            $foreignDropSchema = $this->foreignDropSchema($column);
            $this->compose(key($foreignDropSchema), $foreignDropSchema);
        }
    }

    /**
     * Handle Drop of Foreign keys for down method
     * @return void
     */
    protected function dropForeignDown()
    {
        foreach ($this->reader->dropForeigns() as $column) {
            $this->schemaArray($this->reader->previousLoad()[$column], $column);
            $this->composeForeign($column, "downlines");
        }
    }

    /**
     * Handle Drop of morphs for up method
     * @return void
     */
    protected function dropMorphUp()
    {
        foreach ($this->reader->dropMorphs() as $column) {
            $dropMorphSchema = $this->dropMorphSchema($column);
            $this->compose(key($dropMorphSchema), $dropMorphSchema);
        }
    }

    /**
     * Handle Drop of morphs for down method
     * @return void
     */
    protected function dropMorphDown()
    {
        foreach ($this->reader->dropMorphs() as $column) {
            $this->compose($column, $this->reader->previousLoad()[$column], "downlines");
        }
    }

    /**
     * Handle foreign schemas
     * @param string $column
     * @return void
     */
    protected function composeForeign(string $column, $holderlines = "foreignLines")
    {
        if (! empty($this->preparedForeign)) {
            $this->compose($column, $this->preparedForeign, $holderlines);
            $this->preparedForeign = [];
        }
    }

    /**
     * Handle downlines of foreign schemas
     * @param string $column
     * @return void
     */
    protected function composeDownForeign(string $column)
    {
        if (! empty($this->preparedForeign)) {
            $this->compose($column, $this->preparedForeign, "foreignLines");
            $this->preparedForeign = [];
        }
    }

    /**
     * Compose up migration method
     */
    protected function compose($column, $schema, 
        $linetype = "uplines", $endWrite = true, Closure $afterWrite = null)
    {
        $schema = is_array($schema) ? $schema : $this->schemaArray($schema, $column);
        $writer = "\$table->";
        foreach ($schema as $method => $options) {
            $writer = $this->writeLine($method, $options, $column, $writer);
        }
        
        !$endWrite ?: $writer = $this->endWrite($writer);

        return $afterWrite ? $afterWrite($writer, $linetype) :
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
        return $upwriter . $method . "($column" . $this->flatOptions($options, ! empty($column));
    }

    /**
     * Chain other migration methods
     * @param string $method
     * @param array $options
     * 
     * @return string
     */
    protected function doOther(string $method, array $options = [])
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
        return is_numeric($param) || 
            $this->isBool($param) || $this->isArray($param) ? $param: "'{$param}'";
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
     * Checks if a string literal is an array
     * @param mixed $param
     * @return bool
     */
    protected function isArray($param)
    {
        return strpos($param, "[");
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
        return strpos($schema, "on:");
    }

    /**
     * Checks if a stringed schema contains reference
     * @param string $schema
     * @return bool
     */
    protected function hasReference(string $schema)
    {
        return strpos($schema, "references:");
    }

    /**
     * Checks if a stringed Schema has options
     * @param string $schema
     * @return bool
     */
    protected function hasOption(string $schema)
    {
        return strpos($schema, ":");
    }

    /**
     * Break a stringed schema into an arrayed schema
     * @param string $schema
     * @return array $arrayedSchema
     */
    protected function arrayed(string $schema)
    {
        return explode("|", $schema);
    }

    /**
     * Add reference to foreign
     * @param array $arrayed
     * @return void
     */
    protected function addReference(array $arrayed)
    {
        array_push($arrayed, "references:id");
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
            $options = explode(",", trim(\substr($stringed, $index + 1)));
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

    /**
     * Checks if a column schema definition has morphs
     * @param string $column
     */
    protected function hasMorphs(string $column)
    {
        $schema = $this->reader->currentLoad()[$column];
        $arrayedSchema = $this->schemaArray($schema, $column);
        return array_key_exists("morphs", $arrayedSchema) 
            || array_key_exists("nullableMorphs", $arrayedSchema);
    }

    /**
     * Get Column Drop Schema
     * @return array
     */
    protected function columnDropSchema($column)
    {
        if ($this->hasMorphs($column)) {
            return $this->dropMorphSchema($column);
        }
        if ($column == Constants::SOFT_DELETE) {
            return ["dropSoftDeletes" => []];
        } elseif ($column == Constants::TIMESTAMP) {
            return ["dropTimestamps" => []];
        } elseif ($column == Constants::REMEMBER_TOKEN) {
            return ["dropRememberToken" => []];
        }
        return ["dropColumn" => [$column]];
    }

    /**
     * Get Foreign Drop Schema
     * @param string $column
     * @return array
     */
    protected function foreignDropSchema($column)
    {
        $drop_syntax = $this->writer->schema->table() . "_" . $column . "_foreign";
        return ["dropForeign" => [$drop_syntax]];
    }

    /**
     * Get Primary Key Drop Schema
     * @param string $column
     * @return array
     */
    protected function primaryDropSchema($column)
    {
        $drop_syntax = $this->writer->schema->table() . "_" . $column . "_foreign";
        return ["dropForeign" => [$drop_syntax]];
    }

     /**
     * Get Drop Morph Schema
     * @param string $column
     * @return array
     */
    protected function dropMorphSchema($column)
    {
        return ["dropMorphs" => [$column]];
    }

    /**
     * Column Rename Schema
     * @param string $column
     * @return array
     */
    protected function columnRenameSchema($column)
    {
        return ["renameColumn" => [$column]];
    }
}