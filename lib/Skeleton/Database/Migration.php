<?php
/**
 * Database migration class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Database;

use Skeleton\Database\Migration\Config;
use Skeleton\Database\Migration\Runner;

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
		$reflection = new \ReflectionClass($this);
		$packages = \Skeleton\Core\Package::get_all();
		$filename = $reflection->getFileName();

		if (dirname($filename) == dirname(Config::$migration_directory . '/db_version')) {
			Runner::set_version('project', $this->get_version());
			return;
		}

		$migration_package = null;
		foreach ($packages as $package) {
			if (strpos($package->migration_path, dirname($filename)) === 0) {
				$migration_package = $package;
			}
		}

		if ($migration_package === null) {
			throw new \Exception('No package found');
		}

		Runner::set_version($migration_package->name, $this->get_version());
	}

	/**
	 * Get between versions
	 *
	 * @access public
	 * @param Datetime $start_date
	 * @param Datetime $end_date
	 * @return array $migrations
	 */
	public static function get_between_versions($package = 'project', \Datetime $start_date = null, \Datetime $end_date = null) {
		$migrations = self::get_by_package($package);

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
	 * Get all
	 *
	 * @access public
	 * @return array $migrations
	 */
	public static function get_all() {
		$packages = [];
		$packages[] = 'project';
		foreach (\Skeleton\Core\Skeleton::get_all() as $package) {
			$packages[] = $package->name;
		}
		$migrations = [];
		foreach ($packages as $package) {
			$migrations[$package] = \Skeleton\Database\Migration::get_between_versions($package);
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

		if (strpos($version, '/') === false) {
			$version = 'project/' . $version;
		}

		list($package, $version) = explode('/', $version);
		$migrations = \Skeleton\Database\Migration::get_between_versions($package);

		foreach ($migrations as $migration) {
			if (preg_match('@\\\\([\w]+)$@', get_class($migration), $matches)) {
				$classname = $matches[1];
			} else {
				$classname = get_class($migration);
			}
			if ($version == $classname) {
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
	public static function get_by_package($package_name = 'project') {
		if (!file_exists(Config::$migration_directory)) {
			throw new \Exception('Config::$migration_directory is not set to a valid directory');
		}

		/**
		 * Array with results
		 */
		$migrations = [];

		if ($package_name == 'project') {
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
				include_once Config::$migration_directory . '/' . $file;
				$migrations[] = new $classname();
			}
		} else {

			/**
			 * Make a list of all migrations to be execute
			 */
			$packages = \Skeleton\Core\Skeleton::get_all();

			foreach ($packages as $package) {
				if ($package->name != $package_name) {
					continue;
				}
				if (!file_exists($package->migration_path)) {
					continue;
				}
				$files = scandir($package->migration_path, SCANDIR_SORT_ASCENDING);

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

					$namespace_parts = explode('-', $package->name);
					foreach ($namespace_parts as $key => $namespace_part) {
						$namespace_parts[$key] = ucfirst($namespace_part);
					}
					$namespace = implode('\\', $namespace_parts);

					$classname = $namespace . '\Migration_' . str_replace('.php', '', implode('_', $parts));
					include_once $package->migration_path . '/' . $file;
					$migrations[] = new $classname();
				}
			}
		}


		return $migrations;
	}

}
