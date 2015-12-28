<?php
/**
 * Database Migration Runner class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

use \Skeleton\Database\Database;

namespace Skeleton\Database\Migration;

class Runner {

	/**
	 * Get current version
	 *
	 * @access private
	 * @return Datetime $version
	 */
	public static function get_version() {
		if (!file_exists(Config::$migration_directory . '/db_version')) {
			touch(Config::$migration_directory . '/db_version');
		}

		$current_version = trim(file_get_contents(Config::$migration_directory . '/db_version'));
		if (empty($current_version)) {
			return null;
		}

		return \DateTime::createFromFormat('Ymd His', $current_version);
	}

	/**
	 * Get runnable
	 *
	 * @access public
	 * @return array $migrations
	 */
	public static function get_runnable() {
		$migrations = \Skeleton\Database\Migration::get_between_versions(self::get_version(), null);
		return $migrations;
	}
}
