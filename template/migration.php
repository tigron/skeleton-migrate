<?php

declare(strict_types=1);

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
	public function up(): void {
		$db = Database::get();
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down(): void {
	}
}
