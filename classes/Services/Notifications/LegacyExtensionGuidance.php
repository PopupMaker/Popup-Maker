<?php
/**
 * Legacy extension to Pro guidance.
 *
 * @package PopupMaker\Services\Notifications
 */

namespace PopupMaker\Services\Notifications;

use PopupMaker\Base\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Adds one local, aggregated notice for installed retired extensions.
 */
class LegacyExtensionGuidance extends Service implements Provider {

	/**
	 * Canonical Pro plugin basename.
	 *
	 * @var string
	 */
	const PRO_BASENAME = 'popup-maker-pro/popup-maker-pro.php';

	/**
	 * Wire local notification behavior.
	 *
	 * @return void
	 */
	public function init() {
		$this->remove_legacy_framework_offers();
		add_filter( 'pum_alert_list', [ $this, 'register_alert' ], 14 );
	}

	/**
	 * Register the appropriate locally generated alert.
	 *
	 * @param array<int,array<string,mixed>> $alerts Existing alerts.
	 * @return array<int,array<string,mixed>>
	 */
	public function register_alert( $alerts ) {
		if ( ! is_array( $alerts ) ) {
			$alerts = [];
		}

		if ( ! current_user_can( $this->container->get_permission( 'manage_settings' ) ) ) {
			return $alerts;
		}

		$extensions = $this->container->get( 'legacy_extension_catalog' )->get_installed_items();
		if ( empty( $extensions ) ) {
			return $alerts;
		}

		$pro_state = $this->get_pro_plugin_state();
		$alert     = $this->build_alert( $extensions, $pro_state, $this->get_local_pro_license_state( $pro_state ) );

		if ( null !== $alert ) {
			$alerts[] = $alert;
		}

		return $alerts;
	}

	/**
	 * Build the state-specific, aggregated alert.
	 *
	 * @param array<int,array<string,mixed>>         $extensions        Installed legacy extensions.
	 * @param 'active'|'inactive'|'missing'          $pro_state         Local Pro plugin state.
	 * @param 'valid'|'expired'|'inactive'|'missing' $pro_license_state Local Pro license state.
	 *
	 * @return array<string,mixed>|null
	 */
	public function build_alert( $extensions, $pro_state, $pro_license_state ) {
		if ( empty( $extensions ) ) {
			return null;
		}

		$features       = $this->format_feature_names( wp_list_pluck( $extensions, 'feature_name' ) );
		$has_active     = in_array( true, wp_list_pluck( $extensions, 'active' ), true );
		$has_valid      = in_array( 'valid', wp_list_pluck( $extensions, 'license_state' ), true );
		$base_priority  = $has_active ? 72 : 45;
		$action         = null;
		$notice_variant = '';

		if ( 'active' === $pro_state ) {
			if ( 'expired' !== $pro_license_state ) {
				return null;
			}

			$notice_variant = 'renew';
			$title          = __( 'Review your Popup Maker Pro license', 'popup-maker' );
			$message        = sprintf(
				/* translators: %s: comma-separated feature names. */
				__( 'Your installed legacy features (%s) are already included in Popup Maker Pro. The locally stored Pro license is expired; review it to keep updates and support current.', 'popup-maker' ),
				esc_html( $features )
			);
			$subtitle      = __( 'Pro is active; license needs attention', 'popup-maker' );
			$action        = [
				'text'    => __( 'Review Pro license', 'popup-maker' ),
				'type'    => 'link',
				'action'  => '',
				'href'    => $this->get_license_settings_url(),
				'primary' => true,
			];
			$base_priority = max( 76, $base_priority );
		} elseif ( 'inactive' === $pro_state ) {
			$activation_url = $this->get_pro_activation_url();
			if ( '' === $activation_url ) {
				return null;
			}

			$notice_variant = 'activate';
			$title          = __( 'Activate Popup Maker Pro', 'popup-maker' );
			$message        = sprintf(
				/* translators: %s: comma-separated feature names. */
				__( 'Popup Maker Pro is installed but inactive. Activate it to use %s from the single maintained Pro plugin.', 'popup-maker' ),
				esc_html( $features )
			);
			$subtitle      = __( 'Pro is already installed', 'popup-maker' );
			$action        = [
				'text'    => __( 'Activate Popup Maker Pro', 'popup-maker' ),
				'type'    => 'link',
				'action'  => '',
				'href'    => $activation_url,
				'primary' => true,
			];
			$base_priority = max( 78, $base_priority );
		} elseif ( $has_valid ) {
			$notice_variant = 'consolidate';
			$title          = __( 'Your legacy extensions are available in Popup Maker Pro', 'popup-maker' );
			$message        = sprintf(
				/* translators: %s: comma-separated feature names. */
				__( 'You currently use %s through standalone legacy extensions. Popup Maker Pro combines these features in one maintained plugin when you are ready to consolidate.', 'popup-maker' ),
				esc_html( $features )
			);
			$subtitle = __( 'Evergreen consolidation guidance', 'popup-maker' );
			$action   = $this->get_pro_page_action();
		} else {
			$notice_variant = 'included';
			$title          = __( 'These legacy features are included in Popup Maker Pro', 'popup-maker' );
			$message        = sprintf(
				/* translators: %s: comma-separated feature names. */
				__( 'We found %s installed as legacy extensions. These features are now included in the maintained Popup Maker Pro plugin.', 'popup-maker' ),
				esc_html( $features )
			);
			$subtitle = __( 'Based on plugins installed on this site', 'popup-maker' );
			$action   = $this->get_pro_page_action();
		}

		return [
			'code'           => 'pm_legacy_extensions_to_pro_' . $notice_variant . '_v1',
			'category'       => 'recommendation',
			'priority'       => $base_priority,
			'dismissible'    => true,
			'display_inline' => true,
			'type'           => 'info',
			'title'          => $title,
			'message'        => $message,
			'subtitle'       => $subtitle,
			'icon'           => 'awards',
			'actions'        => [
				$action,
				[
					'text'   => __( 'Dismiss', 'popup-maker' ),
					'type'   => 'action',
					'action' => 'dismiss',
				],
			],
		];
	}

	/**
	 * Remove offer callbacks registered by already-shipped framework copies.
	 *
	 * Those copies cannot be updated on sites with expired extension licenses.
	 * Removing only the known ProUpsell controller callbacks lets a Core update
	 * take ownership without disabling unrelated Core recommendations.
	 *
	 * @return void
	 */
	public function remove_legacy_framework_offers() {
		global $wp_filter;

		$hooks = [
			'admin_notices',
			'admin_enqueue_scripts',
			'plugin_row_meta',
			'pum_alert_list',
		];

		foreach ( $hooks as $hook_name ) {
			if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof \WP_Hook ) {
				continue;
			}

			foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'] ?? null;
					if ( ! $this->is_legacy_framework_offer_callback( $function ) ) {
						continue;
					}

					remove_filter( $hook_name, $function, $priority );
				}
			}
		}
	}

	/**
	 * Identify only Extension Framework ProUpsell callbacks.
	 *
	 * @param mixed $callback WordPress callback.
	 * @return bool
	 */
	private function is_legacy_framework_offer_callback( $callback ) {
		if ( ! is_array( $callback ) || ! isset( $callback[0], $callback[1] ) || ! is_object( $callback[0] ) ) {
			return false;
		}

		$class   = get_class( $callback[0] );
		$methods = [ 'admin_notice', 'enqueue_dismiss_script', 'plugin_row_meta', 'register_panel_notification' ];

		return in_array( $callback[1], $methods, true )
			&& (bool) preg_match( '/\\\\ExtensionFramework\\\\Controllers\\\\Admin\\\\ProUpsell$/', $class );
	}

	/**
	 * Resolve Pro's local plugin state.
	 *
	 * @return 'active'|'inactive'|'missing'
	 */
	private function get_pro_plugin_state() {
		$this->load_plugin_dependencies();

		$plugins = get_plugins();
		if ( ! isset( $plugins[ self::PRO_BASENAME ] ) ) {
			return 'missing';
		}

		$is_active = is_plugin_active( self::PRO_BASENAME )
			|| ( is_multisite() && is_plugin_active_for_network( self::PRO_BASENAME ) );

		return $is_active ? 'active' : 'inactive';
	}

	/**
	 * Read Pro license state only from existing local options.
	 *
	 * @param 'active'|'inactive'|'missing' $pro_state Pro plugin state.
	 * @return 'valid'|'expired'|'inactive'|'missing'
	 */
	private function get_local_pro_license_state( $pro_state ) {
		if ( 'missing' === $pro_state ) {
			return 'missing';
		}

		$data   = get_option( 'popup_maker_license', [] );
		$data   = is_array( $data ) ? $data : [];
		$key    = isset( $data['key'] ) ? trim( (string) $data['key'] ) : '';
		$status = isset( $data['status'] ) && is_array( $data['status'] ) ? $data['status'] : [];

		if ( empty( $status ) ) {
			$legacy_status = get_option( 'popup_maker_pro_license_active' );
			$status        = is_object( $legacy_status ) ? get_object_vars( $legacy_status ) : $legacy_status;
		}

		if ( is_string( $status ) ) {
			if ( 'expired' === $status ) {
				return 'expired';
			}
			return '' !== $key && 'valid' === $status ? 'valid' : ( '' !== $key ? 'inactive' : 'missing' );
		}

		$status = is_array( $status ) ? $status : [];
		if (
			( isset( $status['error'] ) && 'expired' === $status['error'] )
			|| ( isset( $status['license'] ) && 'expired' === $status['license'] )
		) {
			return 'expired';
		}

		if ( '' !== $key && ! empty( $status['success'] ) && isset( $status['license'] ) && 'valid' === $status['license'] ) {
			return 'valid';
		}

		return '' !== $key ? 'inactive' : 'missing';
	}

	/**
	 * Build a nonce-protected activation URL for the installed Pro plugin.
	 *
	 * @return string
	 */
	private function get_pro_activation_url() {
		if ( is_multisite() ) {
			if ( ! current_user_can( 'manage_network_plugins' ) ) {
				return '';
			}

			$url = add_query_arg(
				[
					'action'      => 'activate',
					'plugin'      => self::PRO_BASENAME,
					'networkwide' => 1,
				],
				network_admin_url( 'plugins.php' )
			);
		} else {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return '';
			}

			$url = add_query_arg(
				[
					'action' => 'activate',
					'plugin' => self::PRO_BASENAME,
				],
				admin_url( 'plugins.php' )
			);
		}

		return wp_nonce_url( $url, 'activate-plugin_' . self::PRO_BASENAME );
	}

	/**
	 * Local Popup Maker license settings URL.
	 *
	 * @return string
	 */
	private function get_license_settings_url() {
		return admin_url( 'edit.php?post_type=popup&page=pum-settings#go-pro' );
	}

	/**
	 * Public Pro page action with static UTM parameters.
	 *
	 * @return array<string,mixed>
	 */
	private function get_pro_page_action() {
		return [
			'text'     => __( 'See what is included in Pro', 'popup-maker' ),
			'type'     => 'link',
			'action'   => '',
			'href'     => \PopupMaker\generate_upgrade_url( 'legacy-extension-guidance', 'legacy-to-pro', 'notification' ),
			'primary'  => true,
			'external' => true,
		];
	}

	/**
	 * Format a translated human-readable feature list.
	 *
	 * @param string[] $features Feature names.
	 * @return string
	 */
	private function format_feature_names( $features ) {
		$features = array_values( array_unique( array_filter( array_map( 'strval', $features ) ) ) );

		if ( count( $features ) < 2 ) {
			return isset( $features[0] ) ? $features[0] : '';
		}

		$last = array_pop( $features );
		if ( 1 === count( $features ) ) {
			return sprintf(
				/* translators: 1: first feature name, 2: second feature name. */
				__( '%1$s and %2$s', 'popup-maker' ),
				$features[0],
				$last
			);
		}

		return sprintf(
			/* translators: 1: comma-separated feature names, 2: final feature name. */
			__( '%1$s, and %2$s', 'popup-maker' ),
			implode( ', ', $features ),
			$last
		);
	}

	/**
	 * Load WordPress plugin helpers.
	 *
	 * @return void
	 */
	private function load_plugin_dependencies() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
