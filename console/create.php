<?php
/**
 * migration:create command for Skeleton Console
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Skeleton\Database\Migration\Config;

class Migrate_Create extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('migrate:create');
		$this->setDescription('Create a new empty migration class');
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of your migration');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (Config::$migration_directory !== null) {
			Config::$migration_path = Config::$migration_directory;
		} elseif (Config::$migration_path === null) {
			throw new \Exception('Set a path first in "Config::$migration_path');
		}

		$name = $input->getArgument('name');

		if (strpos($name, "\\") !== false) {
			$name = str_replace('\\', '/', $name);
		}

		if (strpos($name, '/') === false) {
			$path = $this->create_project_migration($name);
		} else {
			$path = $this->create_package_migration($name);
		}

		$output->writeln('New migration template created at ' . $path );

		return 0;
	}

	/**
	 * Create package migration
	 *
	 * @access private
	 * @param string $name
	 * @return string $path
	 */
	private function create_package_migration($name) {
		list($packagename, $name) = explode('/', $name);

		$skeleton_packages = \Skeleton\Core\Skeleton::get_all();
		$package = null;
		foreach ($skeleton_packages as $skeleton_package) {
			if ($skeleton_package->name == $packagename) {
				$package = $skeleton_package;
			}
		}

		if ($package === null) {
			throw new \Exception('Package ' . $packagename . ' not found');
		}

		if (!file_exists($package->migration_path)) {
			mkdir($package->migration_path);
		}

		$name = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $name);

		$datetime = date('Ymd_His');
		$filename = $datetime . '_' . strtolower($name) . '.php';
		$classname = 'Migration_' . $datetime . '_' . ucfirst($name);

		$namespace_parts = explode('-', $package->name);
		foreach ($namespace_parts as $key => $namespace_part) {
			$namespace_parts[$key] = ucfirst($namespace_part);
		}
		$namespace = implode('\\', $namespace_parts);

		$template = file_get_contents(__DIR__ . '/../template/migration.php');
		$template = str_replace('%%namespace%%', 'namespace ' . $namespace . ';' . "\n", $template);
		$template = str_replace('%%classname%%', $classname, $template);
		file_put_contents($package->migration_path . '/' . $filename, $template);
		return $package->migration_path . '/' . $filename;
	}

	/**
	 * Create project migration
	 *
	 * @access private
	 * @param string $name
	 * @return string $path
	 */
	private function create_project_migration($name) {
		$name = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $name);

		$datetime = date('Ymd_His');
		$filename = $datetime . '_' . strtolower($name) . '.php';
		$classname = 'Migration_' . $datetime . '_' . ucfirst($name);

		$template = file_get_contents(__DIR__ . '/../template/migration.php');
		$template = str_replace('%%namespace%%', '', $template);
		$template = str_replace('%%classname%%', $classname, $template);
		file_put_contents(Config::$migration_path . '/' . $filename, $template);
		return Config::$migration_path . '/' . $filename;
	}
}
