# Smooth Migrations  
A Flexible way to manage Laravel Migrations  
Smooth Migration is a Laravel package that allows you abstract your schema definitions from your migration files.  
This allows you to do two simple things really well.  
* 1. Specify which schema to run first. Very useful for situations where you need foreign key references without   
needing to change migration file name.
* 2. Update your schema on the go. Need to drop a column? Add a new column? No need to generate migration files for that.  
Just update the existing schema definition and the package generates the necessary migration files

Smooth Migrations handles the generation of all migration files with their appropriate methods.  

## Installation.

Install via composer: 

```composer require nwogu/smooth-migration```

## Setup:

Publish the config files with the ```php artisan vendor:publish``` command 

The default schema directory is in database/schemas

## Usage:

Create a schema file for your table with the command and pass it the table name:  
```php artisan smooth:create product```

This will create a schema template for you to define your migrations:  
```
<?php

use Nwogu\SmoothMigration\Definition;
use Nwogu\SmoothMigration\Abstracts\Schema;

class ProductSchema extends Schema
{

    /**
    * Table Name
    * 
    * @var string
    */
    protected $table = "product";

     /**
     * Specify Schema to run first.
     *
     * @array
     */
     protected $runFirst = [

     ];


    /**
     * Schema Definitions
     */
    protected function define(Definition $definition)
    {
        $definition->name = "string";
        $definition->is_active = "integer|default:true";
        $definition->description = "text|nullable";
        $definition->fileable = "morphs";
        $definition->user_id = "integer|on:users|onDelete:cascade";
    }

}
```


To generate the migrations from the schemas, call the ```php artisan make:migration -s``` command

This command will generate the appropriate migration files.  

## Supported Schema Definitions  

```
$definition->id = "bigIncrements";
$definition->votes = "bigInteger";
$definition->data = "binary";
$definition->confirmed = "boolean";
$definition->name = "char:100";
$definition->created_at = "date";
$definition->created_at = "dateTime";
$definition->amount = "decimal:8,2";
$definition->amount = "double:8,2";
$definition->amount = "float:8,2";
$definition->positions = "geometry";
$definition->positions = "geometryCollection";
$definition->votes = "integer";
$definition->visitor = "ipAddress";
$definition->options = "json";
$definition->options = "jsonb";
$definition->positions = "lineString";
$definition->description = "longText";
$definition->device = "macAddress";
$definition->id = "mediumIncrements";
$definition->votes = "mediumInteger";
$definition->description = "mediumText";
$definition->taggable = "morphs";
$definition->positions = "multiLineString";
$definition->positions = "multiPoint";
$definition->positions = "multiPolygon";
$definition->taggable = "nullableMorphs";
$definition->position = "point";
$definition->positions = "polygon";
$definition->rememberToken = "rememberToken";
$definition->id = "smallIncrements";
$definition->votes = "smallInteger";
$definition->softDeletes = "softDeletes";
$definition->name = "string:100";
$definition->description = "text";
$definition->sunrise = "time";
$definition->sunrise = "timeTz";
$definition->added_on = "timestamp";
$definition->added_on = "timestampTz";
$definition->id = "tinyIncrements";
$definition->votes = "tinyInteger";
$definition->votes = "unsignedBigInteger";
$definition->amount = "unsignedDecimal:8,2";
$definition->votes = "unsignedInteger";
$definition->votes = "unsignedMediumInteger";
$definition->votes = "unsignedSmallInteger";
$definition->votes = "unsignedTinyInteger";
$definition->id = "uuid";
$definition->birth_year = "year";
```  
Auto Increments and timestamps are inserted by default, to disable this, specify the attributes in your schema class:  

```
class ProductSchema extends Schema
{
    /**
     * Auto Incrementing Id
     * @var bool
     */
    protected $autoIncrement = false
    
    /**
     * Add Timestamps
     * @var bool
     */
    protected $timestamps = false
    
```  
## Running Migrations in Order  

You can specify which schema to run first with the ```runFirst``` property  

```
class ProductSchema extends Schema
{
    /**
     * Auto Incrementing Id
     * @var bool
     */
    protected $runFirst = [
        OutletSchema::class,
        TaxSchema::class
    ];
    
```   
However, you can't specify a ```runFirst``` for a schema if already defined in the parent schema, take the example above:  
The runFirst property of OutletSchema Class or TaxSchema Class cannot contain the ProductSchema Class. 

## Updating Schemas

Make changes to your schema class directly.

Run the ```php artisan make: migration -s``` command, this will generate the appropriate migration files.

To run your migrations:
```php artisan migrate```

