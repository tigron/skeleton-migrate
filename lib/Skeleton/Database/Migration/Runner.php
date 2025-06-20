<?php
/**
 * Database Migration Runner class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Database\Migration;

use \Skeleton\Database\Database;

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
		if (Config::$migration_directory !== null) {
			Config::$migration_path = Config::$migration_directory;
		} elseif (Config::$migration_path === null) {
			throw new \Exception('Set a path first in "Config::$migration_path');
		}

		if (file_exists(Config::$migration_path . '/db_version')) {
			$version_file = trim(file_get_contents(Config::$migration_path . '/db_version'));
			$version = json_decode($version_file, true);
		} else {
			$version = null;
		}

		if ($version === null and file_exists(Config::$migration_path . '/db_version')) {
			// This is an old db_version file, let's update it
			$version = [
				'project' => $version_file
			];
			file_put_contents(Config::$migration_path . '/db_version', json_encode($version));
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
		$table_exists = false;

		try {
			$result = $db->get_columns($table);
			if (count($result) > 0) {
				$table_exists = true;
			}
		} catch (\Exception $e) {}

		if ($table_exists === false) {
			if ($db->get_dbms() == 'mysql') {
				$dt_type = 'datetime';
			} else {
				$dt_type = 'timestamp';
			}

			$db->query('
				CREATE TABLE ' . $db->quote_identifier($table) . ' (
				  ' . $db->quote_identifier('package') . ' varchar(32) NOT NULL,
				  ' . $db->quote_identifier('version') . ' ' . $dt_type . ' NOT NULL
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

		$version = $db->get_one('SELECT version FROM ' . $db->quote_identifier($table) . ' WHERE package=?', [ $package ]);
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
	public static function set_version($package, \Datetime  $version) {
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

		if (Config::$migration_directory !== null) {
			Config::$migration_path = Config::$migration_directory;
		} elseif (Config::$migration_path === null) {
			throw new \Exception('Set a path first in "Config::$migration_path');
		}

		if (file_exists(Config::$migration_path . '/db_version')) {
			$version_file = trim(file_get_contents(Config::$migration_path . '/db_version'));
			$versions = json_decode($version_file, true);
		} else {
			$versions = [];
		}

		$versions[$package] = $version->format('Ymd His');
		file_put_contents(Config::$migration_path . '/db_version', json_encode($versions));
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

		$row = $db->get_row('SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE package=?', [ $package ]);
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

		$sorted_migrations = [];

		foreach ($migrations as $package_migrations) {
			foreach ($package_migrations as $migration) {
				$version = $migration->get_version()->format('YmdHis');
				$append = 0;
				while (isset($sorted_migrations[$version . $append])) {
					$append++;
				}
				$sorted_migrations[$version . $append] = $migration;
			}
		}
		ksort($sorted_migrations);

		return $sorted_migrations;
	}
}
