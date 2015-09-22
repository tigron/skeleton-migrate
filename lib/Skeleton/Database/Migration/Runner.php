<?php
/**
 * Database Migration Runner class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

use Skeleton\Database\Database;

namespace Skeleton\Database\Migration;

class Runner {

	/**
	 * Up
	 *
	 * @access public
	 */
	public static function up() {
		if (!file_exists(Config::$migration_directory)) {
			throw new \Exception('Config::$migration_directory is not set to a valid directory');
		}

		if (!file_exists(Config::$migration_directory . '/db_version')) {
			touch(Config::$migration_directory . '/db_version');
		}

		/**
		 * Get the current database version from db_version
		 */
		$current_version = file_get_contents(Config::$migration_directory . '/db_version');
		$database_version = \DateTime::createFromFormat('Ymd His', $current_version);

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

			list($date, $time, $name) = explode('_', $file);
			$datetime = \Datetime::createFromFormat('Ymd His', $date . ' ' . $time);

			if ($database_version !== false) {
				$interval = $datetime->diff($database_version);
			}
		}

		return self::run($files);
	}

	/**
	 * Run
	 *
	 * @access private
	 * @param array $filenames
	 */
	private static function run($filenames = []) {
		$log = '';
		$log .= 'Running migrations' . "\n";
		foreach ($filenames as $filename) {
			$log .= "\t" . $filename . ': ' . "\t";
			$parts = explode('_', $filename);
			foreach ($parts as $key => $part) {
				$parts[$key] = ucfirst($part);
			}

			$classname = 'Migration_' . str_replace('.php', '', implode('_', $parts));
			include Config::$migration_directory . '/' . $filename;
			$migration = new $classname();

			try {
				$migration->up();
				$log .= '<info>ok</info>';
			} catch (Exception $e) {
				$log .= '<error>error</error>';
			}
		}
		return $log;
	}

}
