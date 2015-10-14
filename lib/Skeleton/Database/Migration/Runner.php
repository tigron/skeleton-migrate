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
	 * Up
	 *
	 * @access public
	 */
	public static function up(&$log) {
		$migrations = \Skeleton\Database\Migration::get_between_versions(self::get_version(), null);

		$log = '';
		$log .= 'Running migrations' . "\n";
		foreach ($migrations as $migration) {
			$log .= "\t" . get_class($migration) . ': ' . "\t";
			try {
				$migration->run('up');
				$log .= '<info>ok</info>';
			} catch (Exception $e) {
				$log .= '<error>' . $e->getMessage() . '</error>';
				return 1;
			}
			$log .= "\n";
		}
		return 0;
	}

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
}
