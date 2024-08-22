<?php
/**
 * Deprecated classes
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 *
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, PSR2.Classes.PropertyDeclaration.ScopeMissing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PopMake_License Class
 *
 * @deprecated 1.5.0
 *
 * Use PUM_Extension_License instead.
 */
class PopMake_License extends PUM_Extension_License {}

/**
 * PopupMaker_Plugin_Updater
 *
 * @deprecated 1.5.0 Use PUM_Extension_Updater.
 */
class PopupMaker_Plugin_Updater extends PUM_Extension_Updater {}

/**
 * Popmake_Cron Class
 *
 * This class handles scheduled events
 *
 * @since 1.3.0
 * @deprecated 1.8.0
 */
class Popmake_Cron extends PUM_Utils_Cron {}

/**
 * Class PUM_Popup_Query
 *
 * @deprecated 1.8.0
 */
class PUM_Popup_Query {

	/**
	 * The args to pass to the pum_get_popups() query
	 *
	 * @var array
	 * @access public
	 */
	public $args = [];

	/**
	 * Default query arguments.
	 *
	 * Not all of these are valid arguments that can be passed to WP_Query. The ones that are not, are modified before
	 * the query is run to convert them to the proper syntax.
	 *
	 * @param array $args The array of arguments that can be passed in and used for setting up this popup query.
	 */
	public function __construct( $args = [] ) {
		$this->args = $args;
	}

	/**
	 * Retrieve popups.
	 *
	 * The query can be modified in two ways; either the action before the
	 * query is run, or the filter on the arguments (existing mainly for backwards
	 * compatibility).
	 *
	 * @access public
	 * @return object
	 */
	public function get_popups() {
		return pum_get_popups( $this->args );
	}
}

/**
 * Class PUM
 *
 * @deprecated 1.8.0 - Don't use this. Use Popup_Maker instead.
 */
class PUM {
	const DB_VER   = null;
	const VER      = null;
	static $DB_VER = null;
	static $VER    = null;
}
