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
```php artisan smooth:create products```

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
    }

}
```


To generate the migrations from the schemas, call the ```php artisan make:migration -s``` command

This command will generate the appropriate migration files.

## Updating Schema Files

Make changes to your schema files directly.

Run the ```php artisan make: migration -s``` command, this will generate the appropriate migration files.

To run your migrations:
```php artisan migrate```

