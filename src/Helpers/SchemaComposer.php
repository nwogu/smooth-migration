<?php

namespace Nwogu\SmoothMigration\Helpers;

use Nwogu\SmoothMigration\Helpers\SchemaWriter;
use const Nwogu\SmoothMigration\Helpers\SCHEMA_CREATE_ACTION;
use const Nwogu\SmoothMigration\Helpers\SCHEMA_UPDATE_ACTION;

class SchemaComposer
{
    /**
     * Lines for Up method
     * @var array
     */
    protected $uplines = [];

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
        SCHEMA_CREATE_ACTION => "addition",
        SCHEMA_UPDATE_ACTION => [
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
     * Handle Schema Composition
     * @return void
     */
    public function init()
    {
        $composeMethod = $this->composeMethods[$this->writer->action()];
        if (is_string($composeMethod) && is_callable($composeMethod)) {
            return $this->$composeMethod();
        }
        foreach ($composeMethod as $callable) {
            return !is_callable($callable) ?: $this->$callable();
        }
    }

    /**
     * Create Addition Schemas
     * @return void
     */
    protected function addition()
    {
    }
}