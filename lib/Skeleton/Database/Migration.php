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
	 * Get package
	 *
	 * @access public
	 * @return \Skeleton\Core\Skeleton $skeleton
	 */
	public function get_skeleton() {
		$packages = \Skeleton\Core\Skeleton::get_all();
		$reflection = new \ReflectionClass(get_class($this));
		$filename = $reflection->getFileName();

		foreach ($packages as $package) {
			if (strpos($package->migration_path, dirname($filename)) === 0) {
				return $package;
			}
		}
		throw new \Exception('This migration is not part of a skeleton-package');
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
		$packages = \Skeleton\Core\Skeleton::get_all();
		$filename = $reflection->getFileName();

		if (Config::$migration_directory !== null) {
			Config::$migration_path = Config::$migration_directory;
		} elseif (Config::$migration_path === null) {
			throw new \Exception('Set a path first in "Config::$migration_path');
		}

		if (substr(Config::$migration_path, -1) == '/') {
			$migration_path = substr(Config::$migration_path, 0, -1);
		} else {
			$migration_path = Config::$migration_path;
		}

		if (dirname($filename) == $migration_path) {
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
		if (Config::$migration_directory !== null) {
			Config::$migration_path = Config::$migration_directory;
		} elseif (Config::$migration_path === null) {
			throw new \Exception('Set a path first in "Config::$migration_path');
		}

		if (!file_exists(Config::$migration_path)) {
			throw new \Exception('Config::$migration_path is not set to a valid path');
		}

		/**
		 * Array with results
		 */
		$migrations = [];

		if ($package_name == 'project') {
			$files = scandir(Config::$migration_path, SCANDIR_SORT_ASCENDING);

			foreach ($files as $key => $file) {
				if ($file[0] == '.') {
					unset($files[$key]);
					continue;
				}

				if ($file == 'db_version') {
					unset($files[$key]);
					continue;
				}

				if (!preg_match("/^\d{8}_\d{6}_.*\.php$/", $file)) {
					unset($files[$key]);
					continue;
				}


				$parts = explode('_', $file);
				foreach ($parts as $key => $part) {
					$parts[$key] = ucfirst($part);
				}

				$classname = 'Migration_' . str_replace('.php', '', implode('_', $parts));
				include_once Config::$migration_path . '/' . $file;
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
