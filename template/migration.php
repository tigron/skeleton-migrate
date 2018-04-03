<?php
/**
 * Database migration class
 *
 */
%%namespace%%

use \Skeleton\Database\Database;

class %%classname%% extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}
