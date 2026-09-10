<?php
/**
 * Elementor submissions query fixture.
 *
 * @package Popup_Maker
 */

namespace ElementorPro\Modules\Forms\Submissions\Database;

/**
 * Minimal Elementor query API used by the form integration.
 */
class Query {

	/**
	 * Fixture submissions table.
	 *
	 * @var string
	 */
	public static $table_name = '';

	/**
	 * Get the fixture query instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		return new self();
	}

	/**
	 * Get the submissions table name.
	 *
	 * @return string
	 */
	public function get_table_submissions() {
		return self::$table_name;
	}
}
