<?php

declare(strict_types=1);

/**
 * migration:purge command for Skeleton Console
 *
 * @author Adrian Covrig <adrian.covrig@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Command\Command;
use Skeleton\Database\Migration\Config;

class Migrate_Purge extends \Skeleton\Console\Command {
	/**
	 * Configure the Purge command
	 *
	 * @access protected
	 */
	protected function configure(): void {
		$this->setName('migrate:purge');
		$this->setDescription('Purge migrations older than a given number of months. Default to 3 months.');
		$this->addArgument('months', InputArgument::OPTIONAL, 'Number of months to keep');
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
			return COMMAND::INVALID;
		}

		$months = 6;
		if ($input->getArgument('months') !== null) {
			$months = intval($input->getArgument('months'));
		}

		$migrations = \Skeleton\Database\Migration::get_purgeable($months);

		if (count($migrations) === 0) {
			$output->writeln('No migrations older than ' . $months . ' months are available for purging.');
			return COMMAND::SUCCESS;
		}

		$migrations_to_purge = [];
		foreach ($migrations as $migration) {
			if (preg_match('@\\\\([\w]+)$@', get_class($migration), $matches)) {
				$classname = $matches[1];
			} else {
				$classname = get_class($migration);
			}
			$output->writeln("\t" . 'project / ' . $classname);

			$filename = str_replace('Migration_', '', $classname) . '.php';
			$migration_path = Config::$migration_path . strtolower($filename);
			$migrations_to_purge[] = [
				'migration_path' => $migration_path,
				'classname' => $classname,
			];
		}

		if (count($migrations_to_purge) > 0) {
			$helper = new QuestionHelper();
			$question = new ConfirmationQuestion(
				'Are you sure that you want to purge ' . count($migrations_to_purge) . ' migrations older than ' . $months . ' months?' . "\t",
				false
			);

			if ($helper->ask($input, $output, $question) === false) {
				$output->writeln('No migrations purged.');
				return Command::FAILURE;
			}

			foreach ($migrations_to_purge as $migration) {
				unlink($migration['migration_path']);
				$output->writeln("\t" . ' <info>PURGED</info>' . "\t" . 'project / ' . $migration['classname']);
			}
		}

		return COMMAND::SUCCESS;
	}
}
