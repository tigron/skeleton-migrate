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
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (Config::$migration_directory !== null) {
			Config::$migration_path = Config::$migration_directory;
		} elseif (Config::$migration_path === null) {
			$output->writeln('<error>Config::$migration_path is not set to a valid migration_path</error>');
			return 1;
		}

		if (isset(\Skeleton\Object\Config::$cache_handler) AND \Skeleton\Object\Config::$cache_handler != '') {
			$output->writeln('Flush object cache');
			$handler = '\Skeleton\Object\Cache\Handler\\' . \Skeleton\Object\Config::$cache_handler;
			$handler::flush();
		}

		$daemon_running = false;

		if (class_exists('Skeleton\Transaction\Daemon')) {
			$daemon_running = \Skeleton\Transaction\Daemon::is_running();
			if ($daemon_running) {
				$output->writeln('Stopping transaction daemon');
				\Skeleton\Transaction\Daemon::stop();
			}
		}

		$output->writeln('Running migrations');
		$migrations = \Skeleton\Database\Migration\Runner::get_runnable();

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
			$output->write("\t" . $package . ' / ' . $classname . ' ');
			try {
				$migration->run('up');
				$output->writeln('<info>ok</info>');
			} catch (\Exception $e) {
				$output->writeln('<error>' . $e->getMessage() . '</error>');
				$output->writeln('<comment>' . $e->getTraceAsString() . '</comment>');
				return 1;
			}
		}

		$output->writeln('Database up-to-date' );

		if (class_exists('Skeleton\Transaction\Daemon')) {
			if ($daemon_running) {
				\Skeleton\Transaction\Daemon::start();
				$output->writeln('Starting transaction daemon');
			}
		}

		return 0;
	}
}
