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
 * @since 1.21.0
 */
function get_current_popup() {
	return get_global( 'current_popup', null );
}

/**
 * Set the current popup.
 *
 * @param Popup|null $popup
 *
 * @since 1.21.0
 */
function set_current_popup( $popup ) {
	set_global( 'current_popup', $popup );
	/**
	 * Here for backward compatibility.
	 *
	 * @deprecated 1.21.0
	 */
	pum()->current_popup = $popup;
}
