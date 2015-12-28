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

class Migrate_Up extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('migrate:up');
		$this->setDescription('Update the database with all existing migrations');
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

		$output->writeln('Running migrations');

		$migrations = \Skeleton\Database\Migration\Runner::get_runnable();

		if (count($migrations) == 0) {
			$output->writeln('Database up-to-date' );
			return 1;
		}

		foreach ($migrations as $migration) {
			$output->write("\t" . get_class($migration) . "\t");
			try {
				$migration->run('up');
				$output->writeln('<info>ok</info>');
			} catch (Exception $e) {
				$output->writeln('<error>' . $e->getMessage() . '</info>');
				return 0;
			}
		}
		$output->writeln('Database up-to-date' );
		return 1;
	}
}
