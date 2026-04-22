<?php
/**
 * Notifications marker integration.
 *
 * Attaches a green "[!]" marker + notification count to the existing Popup
 * Maker admin sidebar menu item and frontend admin bar node. Clicking the
 * marker (or the new "Notifications" sub-node in the admin bar dropdown)
 * navigates to the Popups list with the slide-in panel pre-opened.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Admin;

use PUM_Utils_Alerts;
use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class ToolbarNotifications.
 *
 * @since 1.23.0
 */
class ToolbarNotifications extends Controller {

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		// Admin sidebar menu integration — runs after the existing alerts
		// count append at priority 999, we use a higher priority so our
		// marker replaces/augments it consistently.
		add_action( 'admin_menu', [ $this, 'inject_sidebar_marker' ], 1000 );

		// Frontend + wp-admin top bar integration.
		add_action( 'admin_bar_menu', [ $this, 'inject_admin_bar_marker' ], 1001 );

		add_action( 'wp_head', [ $this, 'print_styles' ] );
		add_action( 'admin_head', [ $this, 'print_styles' ] );
		add_action( 'wp_footer', [ $this, 'print_marker_bootstrap' ] );
		add_action( 'admin_footer', [ $this, 'print_marker_bootstrap' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_panel' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_panel' ] );
	}

	/**
	 * Tiny always-loaded bootstrap that records a "please open the
	 * notifications panel" flag in localStorage when a marker is clicked.
	 *
	 * The marker itself is wrapped by the normal Popup Maker menu anchor,
	 * so letting the browser follow that anchor lets WP 7.0's soft-navigation
	 * take over and avoids a full-page flash. The flag is consumed by the
	 * React panel on the destination page.
	 *
	 * On pages where the panel IS already mounted, React's own click listener
	 * handles the interaction in-place and this script doesn't need to act.
	 *
	 * @return void
	 */
	public function print_marker_bootstrap() {
		if ( ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return;
		}

		$panel_url = admin_url( 'edit.php?post_type=popup' );
		?>
		<script id="pum-notifications-marker-bootstrap">
		( function () {
			var FLAG_KEY = 'pumOpenNotifications';
			var TTL_MS = 30 * 1000;
			var PANEL_URL = <?php echo wp_json_encode( $panel_url ); ?>;

			function setFlag() {
				try {
					window.localStorage.setItem(
						FLAG_KEY,
						JSON.stringify( { v: 1, exp: Date.now() + TTL_MS } )
					);
				} catch ( err ) {
					// localStorage unavailable — panel simply won't auto-open.
				}
			}

			document.addEventListener( 'click', function ( e ) {
				var marker = e.target && e.target.closest
					? e.target.closest( '[data-pum-notifications-trigger]' )
					: null;
				if ( ! marker ) {
					return;
				}

				// If the React panel is on this page, it handles the click
				// in-place via its own listener. Do NOT set the flag —
				// otherwise the next page navigation (via a link inside
				// the panel) would reopen the panel even though the user
				// never asked for it.
				if ( document.getElementById( 'pum-notifications-panel-root' ) ) {
					return;
				}

				// Find the nearest anchor ancestor and check whether it has a
				// real destination. If not (e.g. the frontend admin bar wraps
				// the marker in an anchor with href="#popup-maker"), we must
				// navigate ourselves.
				var anchor = marker.closest( 'a' );
				var href = anchor ? ( anchor.getAttribute( 'href' ) || '' ) : '';
				var hasUsefulHref = href &&
					href.charAt( 0 ) !== '#' &&
					href.indexOf( 'javascript:' ) !== 0;

				setFlag();

				if ( ! hasUsefulHref ) {
					e.preventDefault();
					e.stopPropagation();
					window.location.href = PANEL_URL;
				}
				// Else: let the browser follow the real anchor (soft-nav
				// eligible), the flag is consumed on the next page mount.
			}, true );
		}() );
		</script>
		<?php
	}

	/**
	 * Enqueue the notifications panel script when the current request is a
	 * Popup Maker admin page or when the URL asks for the panel to open.
	 *
	 * @return void
	 */
	public function maybe_enqueue_panel() {
		if ( ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return;
		}

		$should_load = false;

		if ( is_admin() && function_exists( 'pum_is_admin_page' ) && pum_is_admin_page() ) {
			$should_load = true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $should_load && isset( $_GET['pum-notifications'] ) ) {
			$should_load = true;
		}

		if ( ! $should_load ) {
			return;
		}

		wp_enqueue_script( 'popup-maker-admin-notifications' );
		wp_enqueue_style( 'popup-maker-admin-notifications' );
	}

	/**
	 * Append the notification marker to the Popup Maker admin sidebar menu item.
	 *
	 * Replaces the existing `append_alert_count` red-bubble behavior for the
	 * panel-eligible count, positioned flush right, full height.
	 *
	 * @return void
	 */
	public function inject_sidebar_marker() {
		global $menu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		$count = $this->panel_alert_count();

		if ( $count < 1 ) {
			return;
		}

		foreach ( $menu as $key => $item ) {
			if ( ! isset( $item[2] ) || 'edit.php?post_type=popup' !== $item[2] ) {
				continue;
			}

			$marker = $this->marker_html( $count );

			$title       = isset( $menu[ $key ][0] ) ? (string) $menu[ $key ][0] : '';
			$clean_title = $this->strip_update_plugins_badge( $title );

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$menu[ $key ][0] = $clean_title . $marker;
			break;
		}
	}

	/**
	 * Remove any `<span class="update-plugins ...">...</span>` badge from
	 * a menu title string.
	 *
	 * Tolerates attribute order, extra classes, whitespace/newlines, and
	 * single-level nested spans (which is how WP renders the inner counter
	 * span inside the outer bubble). Regex-based because WordPress's
	 * HTML Tag Processor doesn't yet support node removal.
	 *
	 * @param string $title Raw menu title HTML.
	 * @return string
	 */
	protected function strip_update_plugins_badge( $title ) {
		if ( '' === $title ) {
			return '';
		}

		// Match the outer badge span plus any nested spans within it.
		// Use a non-greedy body match anchored to the matching closing
		// </span> so we don't accidentally consume trailing markup.
		$pattern = '#\s*<span\s+[^>]*class="[^"]*\bupdate-plugins\b[^"]*"[^>]*>(?:[^<]*+|<(?!/span\b)[^>]*+>[^<]*+</[^>]+>)*</span>\s*#is';

		$result = preg_replace( $pattern, '', $title );

		return null === $result ? $title : (string) $result;
	}

	/**
	 * Append the notification marker inside the existing popup-maker admin bar
	 * title, plus add a "Notifications" sub-node in the dropdown.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function inject_admin_bar_marker( $wp_admin_bar ) {
		if ( ! is_object( $wp_admin_bar ) || ! method_exists( $wp_admin_bar, 'get_node' ) ) {
			return;
		}

		if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return;
		}

		$count = $this->panel_alert_count();

		if ( $count < 1 ) {
			return;
		}

		$parent_node = $wp_admin_bar->get_node( 'popup-maker' );

		if ( ! $parent_node ) {
			// Parent node only exists on the frontend (via the existing toolbar
			// controller). If not present here — e.g. inside wp-admin — skip,
			// the admin sidebar marker already handles the wp-admin surface.
			return;
		}

		$marker = $this->marker_html( $count );

		// Append marker to the existing title.
		$wp_admin_bar->add_node( [
			'id'    => 'popup-maker',
			'title' => $parent_node->title . $marker,
			'meta'  => array_merge(
				is_array( $parent_node->meta ) ? $parent_node->meta : [],
				[
					'class' => trim( ( $parent_node->meta['class'] ?? '' ) . ' pum-has-notifications' ),
				]
			),
		] );

		// Add a Notifications sub-node in the dropdown with a glowing count pill.
		// The href points at the plain Popups list URL (no query param) so
		// WP 7.0 soft-nav can handle it; the bootstrap sets a localStorage
		// flag on click that the panel consumes on mount.
		$wp_admin_bar->add_node( [
			'id'     => 'pum-notifications',
			'title'  => sprintf(
				'<span data-pum-notifications-trigger="1">%s<span class="pum-notifications-count">%s</span></span>',
				esc_html__( 'Notifications', 'popup-maker' ),
				esc_html( (string) $count )
			),
			'href'   => esc_url( admin_url( 'edit.php?post_type=popup' ) ),
			'parent' => 'popup-maker',
			'meta'   => [
				'class' => 'pum-notifications-link',
			],
		] );
	}

	/**
	 * Build the marker HTML — a green edge-aligned chip with "!" + count.
	 *
	 * @param int $count Notification count.
	 * @return string
	 */
	protected function marker_html( $count ) {
		return sprintf(
			' <span class="pum-notifications-marker" role="button" tabindex="0" data-pum-notifications-trigger="1" data-count="%d" aria-label="%s"><span class="pum-notifications-marker__icon">!</span></span>',
			(int) $count,
			esc_attr(
				sprintf(
					/* translators: %d: notification count. */
					_n( '%d Popup Maker notification', '%d Popup Maker notifications', (int) $count, 'popup-maker' ),
					(int) $count
				)
			)
		);
	}

	/**
	 * Count of alerts eligible for the panel (excludes inline-rendered blockers).
	 *
	 * @return int
	 */
	protected function panel_alert_count() {
		$alerts = PUM_Utils_Alerts::get_alerts();
		$count  = 0;

		foreach ( $alerts as $alert ) {
			$type    = isset( $alert['type'] ) ? (string) $alert['type'] : 'info';
			$is_bad  = in_array( $type, [ 'error', 'warning' ], true );
			$is_glob = ! empty( $alert['global'] );

			if ( $is_bad || $is_glob ) {
				continue;
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Inline styles for the sidebar + admin bar marker.
	 *
	 * @return void
	 */
	public function print_styles() {
		if ( ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return;
		}

		?>
		<style id="pum-notifications-marker-styles">
			/* Admin sidebar — flush right, full height, wide enough to clear
				the active-state arrow. */
			#adminmenu #menu-posts-popup > a.menu-top {
				position: relative;
				padding-right: 28px;
			}
			#adminmenu #menu-posts-popup .pum-notifications-marker {
				position: absolute;
				top: 0;
				right: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 22px;
				height: 100%;
				background: #17b869;
				color: #fff;
				font-weight: 700;
				font-size: 12px;
				line-height: 1;
				cursor: pointer;
				transition: background 0.15s ease;
			}
			#adminmenu #menu-posts-popup .pum-notifications-marker:hover,
			#adminmenu #menu-posts-popup .pum-notifications-marker:focus {
				background: #13a05c;
				outline: none;
			}
			#adminmenu #menu-posts-popup .pum-notifications-marker__icon {
				display: inline-block;
			}

			/* Frontend + wp-admin top bar — narrow full-height chip. */
			#wpadminbar #wp-admin-bar-popup-maker > .ab-item {
				position: relative;
				padding-right: 22px;
			}
			#wpadminbar #wp-admin-bar-popup-maker .pum-notifications-marker {
				position: absolute;
				top: 0;
				right: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 16px;
				height: 100%;
				margin: 0;
				background: #17b869;
				color: #fff;
				font-weight: 700;
				font-size: 12px;
				line-height: 1;
				cursor: pointer;
				transition: background 0.15s ease;
			}
			#wpadminbar #wp-admin-bar-popup-maker .pum-notifications-marker:hover {
				background: #13a05c;
			}

			/* Dropdown "Notifications (N)" entry — animated glow around the count. */
			#wpadminbar #wp-admin-bar-pum-notifications .pum-notifications-count {
				display: inline-block;
				margin-left: 6px;
				padding: 1px 7px;
				background: #17b869;
				color: #fff;
				font-weight: 600;
				font-size: 11px;
				line-height: 1.4;
				border-radius: 10px;
				animation: pum-notif-glow 2s ease-in-out infinite;
			}
			@keyframes pum-notif-glow {
				0%, 100% {
					box-shadow: 0 0 0 0 rgba( 23, 184, 105, 0.7 );
				}
				50% {
					box-shadow: 0 0 0 6px rgba( 23, 184, 105, 0 );
				}
			}
			@media ( prefers-reduced-motion: reduce ) {
				#wpadminbar #wp-admin-bar-pum-notifications .pum-notifications-count {
					animation: none;
				}
			}
		</style>
		<?php
	}
}
