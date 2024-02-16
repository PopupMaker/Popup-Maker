<?php
/**
 * Upgrade Registry
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a registry for core upgrade routines.
 *
 * @since 1.7.0
 *
 * @see PUM_Batch_Process_Registry
 */
class PUM_Upgrade_Registry extends PUM_Batch_Process_Registry {

	/**
	 * @var string Currently installed version.
	 */
	public $version;

	/**
	 * @var string Upgraded from version.
	 */
	public $upgraded_from;

	/**
	 * @var string Initially installed version.
	 */
	public $initial_version;

	/**
	 * @var PUM_Upgrade_Registry
	 */
	public static $instance;

	/**
	 * @return PUM_Upgrade_Registry
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
			add_action( 'init', [ self::$instance, 'init' ], -9999 );
		}

		return self::$instance;
	}


	/**
	 * Initializes the upgrade registry.
	 */
	public function init() {
		$this->register_upgrades();

		/**
		 * Fires during instantiation of the batch processing registry.
		 *
		 * @param PUM_Upgrade_Registry $this PUM_Abstract_Registry instance.
		 */
		do_action( 'pum_upgrade_process_init', $this );
	}

	/**
	 * Registers upgrade routines.
	 *
	 * @see PUM_Utils_Upgrades::add_routine()
	 */
	private function register_upgrades() {
		/**
		 * Fires during instantiation of the batch processing registry allowing proper registration of upgrades.
		 *
		 * @param PUM_Upgrade_Registry $this PUM_Abstract_Registry instance.
		 */
		do_action( 'pum_register_upgrades', $this );
	}


	/**
	 * Adds an upgrade to the registry.
	 *
	 * @param int   $upgrade_id upgrade ID.
	 * @param array $attributes {
	 *     Upgrade attributes.
	 *
	 * @type string $class upgrade handler class.
	 * @type string $file upgrade handler class file.
	 * }
	 *
	 * @return true Always true.
	 */
	public function add_upgrade( $upgrade_id, $attributes ) {
		$attributes = wp_parse_args(
			$attributes,
			[
				'rules' => [],
				'class' => '',
				'file'  => '',
			]
		);

		// Log an error if it's too late to register the process.
		if ( did_action( 'pum_upgrade_process_init' ) ) {
			pum_log_message( sprintf( 'The %s upgrade process was registered too late. Registrations must occur while/before <code>pum_upgrade_process_init</code> fires.', esc_html( $upgrade_id ) ) );
			return false;
		}

		return $this->register_process( $upgrade_id, $attributes );
	}

	/**
	 * Removes an upgrade from the registry by ID.
	 *
	 * @param string $upgrade_id upgrade ID.
	 */
	public function remove_upgrade( $upgrade_id ) {
		parent::remove_process( $upgrade_id );
	}

	/**
	 * Retrieves registered upgrades.
	 *
	 * @return array The list of registered upgrades.
	 */
	public function get_upgrades() {
		return parent::get_items();
	}

}
