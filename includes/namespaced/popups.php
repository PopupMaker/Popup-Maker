<?php
/**
 * Popup functions.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

use function PopupMaker\plugin;

/**
 * Get the current popup.
 *
 * @return Popup|null
 *
 * @since X.X.X
 */
function get_current_popup() {
	return get_global( 'current_popup', null );
}

/**
 * Set the current popup.
 *
 * @param Popup|null $popup
 *
 * @since X.X.X
 */
function set_current_popup( $popup ) {
	set_global( 'current_popup', $popup );
	/**
	 * Here for backward compatibility.
	 *
	 * @deprecated X.X.X
	 */
	pum()->current_popup = $popup;
}
