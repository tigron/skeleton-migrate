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
	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!file_exists(\Skeleton\Database\Migration\Config::$migration_directory)) {
			$output->writeln('<error>Config::$migration_directory is not set to a valid directory</error>');
			return 1;
		}

		$migrations = \Skeleton\Database\Migration::get_between_versions(\Skeleton\Database\Migration\Runner::get_version(), null);
		if (count($migrations) > 0) {
			$output->writeln('There are ' . count($migrations) . ' outstanding migrations:');
			foreach ($migrations as $migration) {
				$output->writeln("\t" . get_class($migration));
			}
		} else {
			$output->writeln('Database is up-to-date');
		}
		return 0;
	}
}
