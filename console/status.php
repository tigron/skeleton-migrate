<?php
/**
 * migration:status command for Skeleton Console
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

class Migrate_Status extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('migrate:status');
		$this->setDescription('Check if there are migrations that are not executed yet');
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
			$output->writeln('<error>Config::$migration_path is not set to a valid path</error>');
			return 1;
		}

		$migrations = \Skeleton\Database\Migration\Runner::get_runnable();

		if (count($migrations) > 0) {
			$output->writeln('There are ' . count($migrations) . ' outstanding migrations:');
		} else {
			$output->writeln('Database is up-to-date');
		}


		$migration_count = 0;
		foreach ($migrations as $migration) {
			try {
				$package = $migration->get_skeleton();
				$package = $package->name;
			} catch (\Exception $e) {
				$package = 'project';
			}


			if (preg_match('@\\\\([\w]+)$@', get_class($migration), $matches)) {
				$classname = $matches[1];
			} else {
				$classname = get_class($migration);
			}
			$output->writeln("\t" . $package . ' / ' . $classname);
		}

		return 0;
	}
}
