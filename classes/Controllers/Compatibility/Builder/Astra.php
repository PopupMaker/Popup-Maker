<?php
/**
 * Astra Builder compatibility controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Builder;

use PopupMaker\Plugin\Controller;
use PopupMaker\Utils\QueryGlobals;

defined( 'ABSPATH' ) || exit;

/**
 * Protect the pending main loop while Astra Custom Layouts render in popups.
 *
 * Astra Custom Layouts can delegate rendering to a page builder. Some archive
 * widgets alter the main query when the layout is rendered before the theme's
 * main loop. Scope query isolation to popup content that actually embeds an
 * Astra Custom Layout so ordinary popup rendering remains untouched.
 *
 * @since 1.25.0
 */
class Astra extends Controller {

	/**
	 * Query snapshots for nested popup content filters.
	 *
	 * A false entry means that invocation did not contain an Astra layout.
	 *
	 * @var array<int, array<string, mixed>|false>
	 */
	private $query_snapshots = [];

	/**
	 * Initialize compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pum_popup_content', [ $this, 'capture_query_globals' ], 1, 2 );
		add_filter( 'pum_popup_content', [ $this, 'restore_query_globals' ], PHP_INT_MAX );
	}

	/**
	 * Capture query globals when popup content embeds an Astra Custom Layout.
	 *
	 * @param mixed $content  Popup content (passed through unchanged).
	 * @param mixed $popup_id ID of the popup being rendered.
	 * @return mixed
	 */
	public function capture_query_globals( $content, $popup_id = 0 ) {
		$should_capture = is_string( $content )
			&& false !== strpos( $content, '[astra_custom_layout' )
			&& has_shortcode( $content, 'astra_custom_layout' );

		$this->query_snapshots[] = $should_capture ? QueryGlobals::capture() : false;

		return $content;
	}

	/**
	 * Restore query globals after popup content has finished rendering.
	 *
	 * @param mixed $content Popup content (passed through unchanged).
	 * @return mixed
	 */
	public function restore_query_globals( $content ) {
		$snapshot = array_pop( $this->query_snapshots );

		if ( is_array( $snapshot ) ) {
			QueryGlobals::restore( $snapshot );
		}

		return $content;
	}
}
