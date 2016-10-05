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
	 * @access public
	 * @param string $package
	 * @return Datetime $version
	 */
	public static function get_version($package = 'project') {
		if (Config::$version_storage == 'file') {
			return self::file_get_version($package);
		} elseif (Config::$version_storage == 'database') {
			return self::database_get_version($package);
		} else {
			throw new \Exception('Incorrect "version_storage" config setting');
		}
	}

	/**
	 * Get the currect version from file
	 *
	 * @access private
	 * @param string $package
	 * @return Datetime $version
	 */
	private static function file_get_version($package) {
		if (file_exists(Config::$migration_directory . '/db_version')) {
			$version_file = trim(file_get_contents(Config::$migration_directory . '/db_version'));
			$version = json_decode($version_file, true);
		} else {
			$version = null;
		}

		if ($version === null and file_exists(Config::$migration_directory . '/db_version')) {
			// This is an old db_version file, let's update it
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
	 * Get the current version from database
	 *
	 * @access private
	 * @param string $package
	 * @return Datetime $version
	 */
	private static function database_get_version($package) {
		if (!class_exists('\Skeleton\Database\Database')) {
			throw new \Exception('Skeleton database is not active');
		}
		$table = Config::$database_table;
		$db = \Skeleton\Database\Database::get();
		try {
			$result = $db->query('DESC ' . $table);
		} catch (\Exception $e) {
			$db->query('
				CREATE TABLE `' . $table . '` (
				  `package` varchar(32) NOT NULL,
				  `version` datetime NOT NULL
				);
			', []);

			$all_packages = [];
			$all_packages[] = 'project';
			foreach (\Skeleton\Core\Skeleton::get_all() as $skeleton_package) {
				$all_packages[] = $skeleton_package->name;
			}
			$migrations = [];
			foreach ($all_packages as $migration_package) {
				$version = self::file_get_version($migration_package);
				if ($version !== null) {
					self::set_version($migration_package, $version);
				}
			}
		}

		$version = $db->get_one('SELECT version FROM `' . $table . '` WHERE package=?', [ $package ]);
		if ($version === null) {
			return null;
		}
		return \DateTime::createFromFormat('Y-m-d H:i:s', $version);
	}

	/**
	 * Set a version
	 *
	 * @access public
	 * @param string $package
	 * @param Datetime $version
	 */
	public static function set_version($package = 'project', \Datetime  $version) {
		if (Config::$version_storage == 'file') {
			return self::file_set_version($package, $version);
		} elseif (Config::$version_storage == 'database') {
			return self::database_set_version($package, $version);
		} else {
			throw new \Exception('Incorrect "version_storage" config setting');
		}
	}

	/**
	 * File Set version
	 *
	 * @access private
	 * @param string $package
	 * @param Datetime $version
	 */
	private static function file_set_version($package, \Datetime $version) {
		self::get_version($package);
		$version_file = trim(file_get_contents(Config::$migration_directory . '/db_version'));
		$versions = json_decode($version_file, true);
		$versions[$package] = $version->format('Ymd His');
		file_put_contents(Config::$migration_directory . '/db_version', json_encode($versions));
	}

	/**
	 * Database Set version
	 *
	 * @access private
	 * @param string $package
	 * @param Datetime $version
	 */
	private static function database_set_version($package, \Datetime $version) {
		$db = \Skeleton\Database\Database::get();
		$table = Config::$database_table;

		$data = [
			'package' => $package,
			'version' => $version->format('Y-m-d H:i:s'),
		];

		$row = $db->get_row('SELECT * FROM `' . $table . '` WHERE package=?', [ $package ]);
		if ($row === null) {
			$db->insert($table, $data);
		} else {
			$db->update($table, $data, 'package=' . $db->quote($package));
		}
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
