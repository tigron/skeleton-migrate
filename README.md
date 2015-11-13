# skeleton-migrate

## Description

Migrations for Skeleton. Migrations are used to track database changes.

## Installation

Installation via composer:

    composer require tigron/skeleton-migrate

## Howto

Set the directory for migrations

    \Skeleton\Database\Migration\Config::$migration_directory  = $some_very_cool_directory;

Create new migration
```
skeleton migrate:create <name>
```  
Get status
```
skeleton migrate:status
```  
Run all pending migrations
```
skeleton migrate:up
```  
Run a specific migration (version is not stored in the version file)
```
skeleton migration:run <Ymd_His>
```  
