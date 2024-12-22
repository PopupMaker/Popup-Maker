<?php
/**
 * Call To Action utility & helper functions.
 *
 * @author    Code Atlantic LLC
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

use function PopupMaker\plugin;

/**
 * Get call to action by ID.
 *
 * @param int $cta_id Call to action ID.
 *
 * @return \PopupMaker\Models\CallToAction|null
 */
function get_cta_by_id( $cta_id = 0 ) {
	return plugin( 'ctas' )->get_by_id( $cta_id );
}

/**
 * Get call to action by UUID.
 *
 * @param string $cta_uuid Call to action UUID.
 *
 * @return \PopupMaker\Models\CallToAction|null
 */
function get_cta_by_uuid( $cta_uuid = '' ) {
	return plugin( 'ctas' )->get_by_uuid( $cta_uuid );
}
