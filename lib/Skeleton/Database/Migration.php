<?php
/**
 * Database migration class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Database;

use Skeleton\Database\Migration\Config;

class Migration {

	/**
	 * Get version
	 *
	 * @access public
	 * @return Datetime $version
	 */
	public function get_version() {
		$classname = get_class($this); // Migration_20150922_203123_Init
		list($migration, $date, $time, $name) = explode('_', $classname);
		return \Datetime::createFromFormat('YmdHis', $date . $time);
	}

	/**
	 * Run
	 *
	 * @access public
	 * @param string up/down
	 */
	public function run($method) {
		$this->$method();
		file_put_contents(Config::$migration_directory . '/db_version', $this->get_version()->format('Ymd His'));
	}

	/**
	 * Get between versions
	 *
	 * @access public
	 * @param Datetime $start_date
	 * @param Datetime $end_date
	 * @return array $migrations
	 */
	public static function get_between_versions(\Datetime $start_date = null, \Datetime $end_date = null) {
		$migrations = self::get_all();
		foreach ($migrations as $key => $migration) {
			if ($start_date !== null) {
				if ($migration->get_version() <= $start_date) {
					unset($migrations[$key]);
					continue;
				}
			}

			if ($end_date !== null) {
				if ($migration->get_version() >= $end_date) {
					unset($migrations[$key]);
					continue;
				}
			}
		}
		return $migrations;

	}

	/**
	 * Get specific version
	 *
	 * @access public
	 * @param string $version
	 * @return Migration
	 */
	public static function get_by_version($version) {
		$migrations = self::get_all();
		foreach ($migrations as $key => $migration) {
			if ($migration->get_version()->format('Ymd_His') == $version) {
				return $migration;
			}
		}

		throw new \Exception('The specified version does not exists.');
	}

	/**
	 * Get all migrations
	 *
	 * @access public
	 * @return array $migrations
	 */
	public static function get_all() {
		if (!file_exists(Config::$migration_directory)) {
			throw new \Exception('Config::$migration_directory is not set to a valid directory');
		}

		/**
		 * Array with results
		 */
		$migrations = [];

		/**
		 * Make a list of all migrations to be execute
		 */
		$files = scandir(Config::$migration_directory, SCANDIR_SORT_ASCENDING);

		foreach ($files as $key => $file) {
			if ($file[0] == '.') {
				unset($files[$key]);
				continue;
			}

			if ($file == 'db_version') {
				unset($files[$key]);
				continue;
			}

			if (!preg_match("/^\d{8}_\d{6}_.*$/", $file)) {
				unset($files[$key]);
				continue;
			}


			$parts = explode('_', $file);
			foreach ($parts as $key => $part) {
				$parts[$key] = ucfirst($part);
			}

			$classname = 'Migration_' . str_replace('.php', '', implode('_', $parts));
			include Config::$migration_directory . '/' . $file;
			$migrations[] = new $classname();
		}
		return $migrations;
	}

}
