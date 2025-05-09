<?php
/**
 * migration:run command for Skeleton Console
 *
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Skeleton\Database\Migration\Config;

class Migrate_Run extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('migrate:run');
		$this->setDescription('Run a specific migration');
		$this->addArgument('name', InputArgument::REQUIRED, 'Name of the migration');
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

		$migration = \Skeleton\Database\Migration::get_by_version($input->getArgument('name'));

		try {
			$migration->up();
			$output->writeln(get_class($migration) . "\t" . ' <info>ok</info>');
		} catch (\Exception $e) {
			$output->writeln(get_class($migration) . "\t" . ' <error>' . $e->getMessage() . '</error>');
			$output->writeln('<comment>' . $e->getTraceAsString() . '</comment>');
			return 1;
		}

		return 0;
	}

}
