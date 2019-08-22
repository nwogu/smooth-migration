<?php

namespace Nwogu\SmoothMigration\Repositories;

use Illuminate\Database\ConnectionResolverInterface as Resolver;

class SmoothMigrationRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the smooth migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database migration repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     * @return void
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
    }

    /**
     * Get the last migration batch.
     * @param string $schemaClass
     *
     * @return object
     */
    public function getLast(string $schemaClass)
    {
        return $this->table()->where('schema_class', $schemaClass)
            ->where('batch', $this->getLastBatchNumber($schemaClass))->first();
    }

    /**
     * Get the last migration path
     * @param string $schemaClass
     * 
     * @return string|bool $migrationPath
     */
    public function getLastMigration(string $schemaClass)
    {
        $lastRun = $this->getLast($schemaClass);

        return $lastRun ? $lastRun->migration_path : false;
    }

    /**
     * Get previous schema load by batch.
     * @param string $schemaClass
     * @param int $batch
     *
     * @return array
     */
    public function previousSchemaLoad(string $schemaClass, int $batch)
    {
        $query =  $this->table()
                ->where('schema_class', $schemaClass);
        while (! $query->where('batch', $batch)->exists() && $batch > 0) {
            $batch--;
        }
        $load = optional($query->where('batch', $batch)->first())->schema_load;
        return json_decode($load, true);
    }

    /**
     * Log that a migration was run.
     *
     * @param  string  $schemaClass
     * @param  string  $schemaLoad
     * @param string|null $migrationPath
     * @param int $batch
     * @return void
     */
    public function log(string $schemaClass, string $schemaLoad, $migrationPath = null, int $batch = 0)
    {
        $record = [
            'schema_class' => $schemaClass, 
            'schema_load' => $schemaLoad,
            'migration_path' => $migrationPath,
            'batch' => $batch
        ];

        $this->table()->insert($record);
    }

    /**
     * Get the next migration batch number.
     * @param string $schemaClass
     *
     * @return int
     */
    public function getNextBatchNumber(string $schemaClass)
    {
        return $this->getLastBatchNumber($schemaClass) + 1;
    }

    /**
     * Get the last migration batch number.
     * @param string $schemaClass
     *
     * @return int
     */
    public function getLastBatchNumber(string $schemaClass)
    {
        return $this->table()->where("schema_class", $schemaClass)
                ->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->increments('id');
            $table->string('schema_class');
            $table->longText('schema_load');
            $table->string('migration_path')->nullable();
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}