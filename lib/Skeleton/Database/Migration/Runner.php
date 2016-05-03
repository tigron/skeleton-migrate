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
	public static function get_version($package = 'project') {
		if (!file_exists(Config::$migration_directory . '/db_version')) {
			touch(Config::$migration_directory . '/db_version');
		}

		$version_file = trim(file_get_contents(Config::$migration_directory . '/db_version'));

		$version = json_decode($version_file, true);
		if ($version === null) {
			// This is an ond db_version file, let's update it
			$version = [
				'project' => $version_file
			];
			file_put_contents(Config::$migration_directory . '/db_version', json_encode($version));
		}

		if (empty($version[$package])) {
			return null;
		}

		return \DateTime::createFromFormat('Ymd His', $version[$package]);
	}

	/**
	 * Set a version
	 *
	 * @access public
	 * @param string $package
	 * @param Datetime $version
	 */
	public static function set_version($package = 'project', \Datetime  $version) {
		self::get_version($package);
		$version_file = trim(file_get_contents(Config::$migration_directory . '/db_version'));
		$versions = json_decode($version_file, true);
		$versions[$package] = $version->format('Ymd His');
		file_put_contents(Config::$migration_directory . '/db_version', json_encode($versions));
	}

	/**
	 * Get runnable
	 *
	 * @access public
	 * @return array $migrations
	 */
	public static function get_runnable() {
		$packages = [];
		$packages[] = 'project';
		foreach (\Skeleton\Core\Skeleton::get_all() as $package) {
			$packages[] = $package->name;
		}
		$migrations = [];
		foreach ($packages as $package) {
			$migrations[$package] = \Skeleton\Database\Migration::get_between_versions($package, self::get_version($package), null);
		}

		return $migrations;
	}
}
