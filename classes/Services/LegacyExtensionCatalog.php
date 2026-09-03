<?php
/**
 * Local legacy-extension catalog.
 *
 * @package PopupMaker\Services
 */

namespace PopupMaker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Detects retired standalone extensions whose features now live in Pro.
 *
 * The bundled records are the compatibility boundary for old extensions that
 * can no longer receive updates. Newer extensions may add local metadata via
 * the catalog filter, but detection never depends on that registration.
 */
class LegacyExtensionCatalog {

	/**
	 * Get the bundled, backwards-compatible catalog.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_bundled_items() {
		return [
			'popup-analytics'               => [
				'feature_name'       => 'Advanced Analytics',
				'plugin_basenames'   => [
					'popup-maker-popup-analytics/popup-maker-popup-analytics.php',
				],
				'license_shortnames' => [ 'popmake_popup_analytics' ],
				'classes'            => [ 'PUM_Popup_Analytics' ],
				'constants'          => [ 'POPMAKE_POPUPANALYTICS_VER' ],
				'priority'           => 100,
			],
			'exit-intent-popups'            => [
				'feature_name'       => 'Exit Intent',
				'plugin_basenames'   => [
					'popup-maker-exit-intent-popups/popup-maker-exit-intent-popups.php',
				],
				'license_shortnames' => [ 'popmake_exit_intent_popups' ],
				'classes'            => [ 'PUM_EIP' ],
				'priority'           => 95,
			],
			'advanced-targeting-conditions' => [
				'feature_name'       => 'Advanced Targeting',
				'plugin_basenames'   => [
					'popup-maker-advanced-targeting-conditions/popup-maker-advanced-targeting-conditions.php',
				],
				'license_shortnames' => [ 'popmake_advanced_targeting_conditions' ],
				'classes'            => [ 'PUM_ATC' ],
				'priority'           => 90,
			],
			'scheduling'                    => [
				'feature_name'       => 'Scheduling',
				'plugin_basenames'   => [
					'popup-maker-scheduling/popup-maker-scheduling.php',
					'pum-scheduling/pum-scheduling.php',
				],
				'license_shortnames' => [ 'popmake_scheduling' ],
				'classes'            => [ 'PUM_Scheduling' ],
				'priority'           => 85,
			],
			'scroll-triggered-popups'       => [
				'feature_name'       => 'Scroll Triggers',
				'plugin_basenames'   => [
					'popup-maker-scroll-triggered-popups/popup-maker-scroll-triggered-popups.php',
					'popup-maker-scroll-triggers/popup-maker-scroll-triggered-popups.php',
				],
				'license_shortnames' => [ 'popmake_scroll_triggered_popups' ],
				'classes'            => [ 'PUM_STP' ],
				'priority'           => 80,
			],
			'advanced-theme-builder'        => [
				'feature_name'       => 'Theme Builder',
				'plugin_basenames'   => [
					'popup-maker-advanced-theme-builder/popup-maker-advanced-theme-builder.php',
				],
				'license_shortnames' => [ 'popmake_advanced_theme_builder' ],
				'classes'            => [ 'PUM_ATB' ],
				'priority'           => 75,
			],
			'forced-interaction'            => [
				'feature_name'       => 'Forced Interaction',
				'plugin_basenames'   => [
					'popup-maker-forced-interaction/popup-maker-forced-interaction.php',
				],
				'license_shortnames' => [ 'popmake_forced_interaction' ],
				'classes'            => [ 'PUM_Forced_Interaction' ],
				'priority'           => 70,
			],
			'terms-conditions-popups'       => [
				'feature_name'       => 'Terms & Conditions',
				'plugin_basenames'   => [
					'popup-maker-terms-conditions-popups/popup-maker-terms-conditions-popups.php',
				],
				'license_shortnames' => [
					'popmake_terms__conditions_popups',
					'popmake_terms_conditions_popups',
				],
				'classes'            => [ 'PUM_TC' ],
				'priority'           => 65,
			],
		];
	}

	/**
	 * Get the bundled catalog plus locally registered metadata.
	 *
	 * Bundled entries cannot be removed by a broken registration callback.
	 * Extensions may enhance an existing record or append another known Pro
	 * feature, and every value is normalized before it is used for detection.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_items() {
		$bundled  = $this->get_bundled_items();
		$filtered = apply_filters( 'popup_maker/legacy_extension_catalog', $bundled );
		$items    = $bundled;

		if ( is_array( $filtered ) ) {
			foreach ( $filtered as $slug => $item ) {
				if ( ! is_string( $slug ) || sanitize_key( $slug ) !== $slug || ! is_array( $item ) ) {
					continue;
				}

				$items[ $slug ] = isset( $items[ $slug ] )
					? $this->merge_item( $items[ $slug ], $item )
					: $item;
			}
		}

		$normalized = [];
		foreach ( $items as $slug => $item ) {
			$item = $this->normalize_item( $item );
			if ( null !== $item ) {
				$normalized[ $slug ] = $item;
			}
		}

		return $normalized;
	}

	/**
	 * Get detected local extension context.
	 *
	 * Optional arguments make the local-only state machine deterministic in
	 * tests. Runtime calls read the same WordPress options and installed plugin
	 * registry already present on the site.
	 *
	 * @param array<string,array<string,mixed>>|null $plugins                Installed plugin headers.
	 * @param string[]|null                          $active_plugins         Site-active plugin basenames.
	 * @param array<string,mixed>|string[]|null      $network_active_plugins Network-active basenames.
	 * @param array<string,mixed>|null               $settings               Popup Maker settings.
	 * @param array<string,mixed>|null               $license_statuses       License status by shortname.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_installed_items( $plugins = null, $active_plugins = null, $network_active_plugins = null, $settings = null, $license_statuses = null ) {
		if ( null === $plugins ) {
			$this->load_plugin_dependencies();
			$plugins = get_plugins();
		}

		$plugins                = is_array( $plugins ) ? $plugins : [];
		$active_plugins         = null === $active_plugins
			? (array) get_option( 'active_plugins', [] )
			: (array) $active_plugins;
		$network_active_plugins = null === $network_active_plugins
			? (array) get_site_option( 'active_sitewide_plugins', [] )
			: (array) $network_active_plugins;
		$network_active_plugins = array_values(
			array_unique(
				array_merge(
					array_keys( $network_active_plugins ),
					array_values( $network_active_plugins )
				)
			)
		);
		$settings               = null === $settings ? get_option( 'popmake_settings', [] ) : $settings;
		$settings               = is_array( $settings ) ? $settings : [];

		$installed = [];
		foreach ( $this->get_items() as $slug => $item ) {
			$basename = $this->find_installed_basename( $item, $plugins );
			$loaded   = $this->is_loaded( $item );

			if ( '' === $basename && ! $loaded ) {
				continue;
			}

			$site_active    = '' !== $basename && in_array( $basename, $active_plugins, true );
			$network_active = '' !== $basename && in_array( $basename, $network_active_plugins, true );

			$installed[] = array_merge(
				$item,
				[
					'slug'            => $slug,
					'plugin_basename' => $basename,
					'active'          => $site_active || $network_active || $loaded,
					'network_active'  => $network_active,
					'license_state'   => $this->get_license_state( $item, $settings, $license_statuses ),
				]
			);
		}

		usort(
			$installed,
			static function ( $left, $right ) {
				if ( ! empty( $left['active'] ) !== ! empty( $right['active'] ) ) {
					return ! empty( $left['active'] ) ? -1 : 1;
				}

				return (int) $right['priority'] <=> (int) $left['priority'];
			}
		);

		return $installed;
	}

	/**
	 * Resolve an exact installed basename for a catalog record.
	 *
	 * @param array<string,mixed>               $item    Catalog record.
	 * @param array<string,array<string,mixed>> $plugins Installed plugins.
	 *
	 * @return string
	 */
	public function find_installed_basename( $item, $plugins ) {
		if ( ! is_array( $item ) || ! is_array( $plugins ) ) {
			return '';
		}

		foreach ( (array) ( $item['plugin_basenames'] ?? [] ) as $basename ) {
			if ( is_string( $basename ) && isset( $plugins[ $basename ] ) ) {
				return $basename;
			}
		}

		return '';
	}

	/**
	 * Resolve locally stored legacy license state for a catalog record.
	 *
	 * @param array<string,mixed>      $item             Catalog record.
	 * @param array<string,mixed>      $settings         Popup Maker settings.
	 * @param array<string,mixed>|null $license_statuses Optional statuses by shortname.
	 *
	 * @return 'valid'|'expired'|'inactive'|'missing'
	 */
	public function get_license_state( $item, $settings = [], $license_statuses = null ) {
		$has_key     = false;
		$has_expired = false;

		foreach ( (array) ( $item['license_shortnames'] ?? [] ) as $shortname ) {
			$key    = isset( $settings[ $shortname . '_license_key' ] )
				? trim( (string) $settings[ $shortname . '_license_key' ] )
				: '';
			$status = is_array( $license_statuses ) && array_key_exists( $shortname, $license_statuses )
				? $license_statuses[ $shortname ]
				: get_option( $shortname . '_license_active' );
			$status = is_object( $status ) ? get_object_vars( $status ) : $status;

			if ( '' !== $key ) {
				$has_key = true;
			}

			if ( is_string( $status ) ) {
				if ( '' !== $key && 'valid' === $status ) {
					return 'valid';
				}
				$has_expired = $has_expired || 'expired' === $status;
				continue;
			}

			if ( ! is_array( $status ) ) {
				continue;
			}

			if (
				'' !== $key
				&& ! empty( $status['success'] )
				&& isset( $status['license'] )
				&& 'valid' === $status['license']
			) {
				return 'valid';
			}

			$has_expired = $has_expired
				|| ( isset( $status['error'] ) && 'expired' === $status['error'] )
				|| ( isset( $status['license'] ) && 'expired' === $status['license'] );
		}

		if ( $has_expired ) {
			return 'expired';
		}

		return $has_key ? 'inactive' : 'missing';
	}

	/**
	 * Merge extension metadata without discarding bundled identifiers.
	 *
	 * @param array<string,mixed> $base     Bundled record.
	 * @param array<string,mixed> $metadata Registered metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function merge_item( $base, $metadata ) {
		$merged = array_merge( $base, $metadata );

		foreach ( [ 'plugin_basenames', 'license_shortnames', 'classes', 'constants' ] as $key ) {
			$merged[ $key ] = array_values(
				array_unique(
					array_merge(
						(array) ( $base[ $key ] ?? [] ),
						(array) ( $metadata[ $key ] ?? [] )
					)
				)
			);
		}

		return $merged;
	}

	/**
	 * Normalize a local metadata record.
	 *
	 * @param array<string,mixed> $item Candidate record.
	 * @return array<string,mixed>|null
	 */
	private function normalize_item( $item ) {
		$feature_name = isset( $item['feature_name'] ) ? trim( wp_strip_all_tags( (string) $item['feature_name'] ) ) : '';
		$basenames    = $this->normalize_basenames( $item['plugin_basenames'] ?? [] );

		if ( '' === $feature_name || empty( $basenames ) ) {
			return null;
		}

		return [
			'feature_name'       => $feature_name,
			'plugin_basenames'   => $basenames,
			'license_shortnames' => $this->normalize_identifiers( $item['license_shortnames'] ?? [], '/^popmake_[a-z0-9_]+$/' ),
			'classes'            => $this->normalize_identifiers( $item['classes'] ?? [], '/^[A-Za-z_\\\\][A-Za-z0-9_\\\\]*$/' ),
			'constants'          => $this->normalize_identifiers( $item['constants'] ?? [], '/^[A-Z][A-Z0-9_]*$/' ),
			'priority'           => isset( $item['priority'] ) ? (int) $item['priority'] : 50,
		];
	}

	/**
	 * Normalize exact plugin basenames.
	 *
	 * @param mixed $basenames Candidate basenames.
	 * @return string[]
	 */
	private function normalize_basenames( $basenames ) {
		$out = [];
		foreach ( (array) $basenames as $basename ) {
			if (
				is_string( $basename )
				&& '' !== $basename
				&& false !== strpos( $basename, '/' )
				&& false === strpos( $basename, '..' )
				&& false === strpos( $basename, '\\' )
				&& '/' !== substr( $basename, 0, 1 )
			) {
				$out[] = $basename;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Normalize class, constant, or option-prefix identifiers.
	 *
	 * @param mixed  $identifiers Candidate identifiers.
	 * @param string $pattern     Validation pattern.
	 * @return string[]
	 */
	private function normalize_identifiers( $identifiers, $pattern ) {
		$out = [];
		foreach ( (array) $identifiers as $identifier ) {
			if ( is_string( $identifier ) && preg_match( $pattern, $identifier ) ) {
				$out[] = $identifier;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Whether a stable identifier proves the extension is currently loaded.
	 *
	 * @param array<string,mixed> $item Catalog record.
	 * @return bool
	 */
	private function is_loaded( $item ) {
		foreach ( (array) ( $item['classes'] ?? [] ) as $class ) {
			if ( class_exists( $class, false ) ) {
				return true;
			}
		}

		foreach ( (array) ( $item['constants'] ?? [] ) as $constant ) {
			if ( defined( $constant ) ) {
				return true;
			}
		}

		return false;
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
