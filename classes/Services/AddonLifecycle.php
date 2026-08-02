<?php
/**
 * Installed add-on lifecycle service.
 *
 * @package PopupMaker\Services
 */

namespace PopupMaker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Activates or deactivates exact catalog add-ons that are already installed.
 */
class AddonLifecycle {

	/**
	 * Catalog service.
	 *
	 * @var AddonCatalog
	 */
	private $catalog;

	/**
	 * Constructor.
	 *
	 * @param AddonCatalog $catalog Catalog service.
	 */
	public function __construct( $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * Activate an already-installed allowlisted add-on.
	 *
	 * @param string $slug Catalog slug.
	 *
	 * @return string|\WP_Error
	 */
	public function activate( $slug ) {
		$basename = $this->get_installed_basename( $slug );

		if ( is_wp_error( $basename ) ) {
			return $basename;
		}

		if ( is_plugin_active( $basename ) ) {
			return $basename;
		}

		try {
			$result = activate_plugin( $basename, '', false, false );
		} catch ( \Throwable $exception ) {
			return new \WP_Error( 'addon_activation_failed', $exception->getMessage() );
		}

		return is_wp_error( $result ) ? $result : $basename;
	}

	/**
	 * Deactivate an active allowlisted add-on.
	 *
	 * @param string $slug Catalog slug.
	 *
	 * @return string|\WP_Error
	 */
	public function deactivate( $slug ) {
		$basename = $this->get_installed_basename( $slug );

		if ( is_wp_error( $basename ) ) {
			return $basename;
		}

		if ( is_multisite() && is_plugin_active_for_network( $basename ) ) {
			return new \WP_Error( 'addon_network_active', __( 'This add-on is network active and must be managed in Network Admin.', 'popup-maker' ) );
		}

		if ( is_plugin_active( $basename ) ) {
			try {
				deactivate_plugins( $basename, false, false );
			} catch ( \Throwable $exception ) {
				return new \WP_Error( 'addon_deactivation_failed', $exception->getMessage() );
			}
		}

		return $basename;
	}

	/**
	 * Resolve an exact installed basename from the hard-coded catalog.
	 *
	 * @param string $slug Catalog slug.
	 *
	 * @return string|\WP_Error
	 */
	private function get_installed_basename( $slug ) {
		$item = $this->catalog->get_item( $slug );

		if ( null === $item ) {
			return new \WP_Error( 'addon_not_allowlisted', __( 'The requested add-on is not in the Popup Maker catalog.', 'popup-maker' ) );
		}

		$basename = $this->catalog->find_installed_basename( $item );

		if ( '' === $basename ) {
			return new \WP_Error( 'addon_not_installed', __( 'The requested add-on is not installed.', 'popup-maker' ) );
		}

		return $basename;
	}
}
