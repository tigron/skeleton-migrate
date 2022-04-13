# skeleton-migrate

## Description

Migrations for Skeleton. Migrations are used to track database changes.

## Installation

Installation via composer:

    composer require tigron/skeleton-migrate

## Howto

Set the path for migrations

    /**
     * \Skeleton\Database\Migration\Config::$migration_directory is deprecated
     * Use \Skeleton\Database\Migration\Config::$migration_path instead
     */
    \Skeleton\Database\Migration\Config::$migration_path = $some_very_cool_path;

Choose where you want to store the version

    \Skeleton\Database\Migration\Config::$version_storage  = 'file';  // Version will be stored in db_version json file


    \Skeleton\Database\Migration\Config::$version_storage  = 'database';  // Version will be stored in a database
    \Skeleton\Database\Migration\Config::$database_table  = 'db_version'; // Version will be stored in this database table

Remark:

   - If the database table does not exists, it will be created automatically
   - If $version_storage is set to 'database' but a db_version file is found, all versions will be converted to the database


Create new migration
```
skeleton migrate:create <name>
skeleton migrate:create <package-name>/<name>
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
skeleton migrate:run <Ymd_His>
```
