<?php
/**
 * Config class
 * Configuration for Skeleton\Database\Migration
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Database\Migration;

class Config {

	/**
	 * The migration directory
	 *
	 * @access public
	 * @var string $migration_directory
	 */
	public static $migration_directory = null;

	/**
	 * Version storage
	 *
	 * @access public
	 * @var string $version_storage (file / database)
	 */
	public static $version_storage = 'file';

	/**
	 * Database table
	 *
	 * @access public
	 * @var string $database_table (db_version), only used if
	                               version_storage = 'database'
	 */
	public static $database_table = 'db_version';

}
