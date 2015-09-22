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
	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!file_exists(\Skeleton\Database\Migration\Config::$migration_directory)) {
			throw new \Exception('Config::$migration_directory is not set to a valid directory');
		}

		$template = file_get_contents(__DIR__ . '/../template/migration.php');
		$name = $input->getArgument('name');
		$name = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $name);

		$datetime = date('Ymd_His');
		$filename = $datetime . '_' . strtolower($name) . '.php';
		$classname = 'Migration_' . $datetime . '_' . ucfirst($name);

		$template = str_replace('%%classname%%', $classname, $template);
		file_put_contents(\Skeleton\Database\Migration\Config::$migration_directory . '/' . $filename, $template);

		$output->writeln('New migration template created at ' . \Skeleton\Database\Migration\Config::$migration_directory . '/' . $filename );
	}
}
